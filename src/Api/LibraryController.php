<?php
/**
 * Controller API Library (Media)
 */

class LibraryController
{
    private RadiusDatabase $db;
    private AuthService $auth;
    private string $uploadDir;
    private array $allowedImageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
    private array $allowedAudioTypes = ['audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/ogg', 'audio/x-m4a'];
    private int $maxImageSize = 2 * 1024 * 1024; // 2MB
    private int $maxAudioSize = 800 * 1024; // 800KB

    public function __construct(RadiusDatabase $db, AuthService $auth)
    {
        $this->db = $db;
        $this->auth = $auth;
        $this->uploadDir = __DIR__ . '/../../web/uploads/media/';

        // Créer le dossier d'upload s'il n'existe pas
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    private function getAdminId(): ?int
    {
        return $this->auth->getAdminId();
    }

    /**
     * Provisionner les entrées média par défaut pour un admin
     */
    private function ensureMediaEntries(?int $adminId): void
    {
        if ($adminId === null) {
            return;
        }

        $pdo = $this->db->getPdo();

        // Vérifier si cet admin a déjà des médias
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM media_library WHERE admin_id = ?");
        $stmt->execute([$adminId]);
        if ((int)$stmt->fetchColumn() > 0) {
            return;
        }

        // Créer les slots vides pour cet admin
        $pdo->prepare("
            INSERT IGNORE INTO media_library (media_type, media_key, description, admin_id) VALUES
            ('logo', 'company_logo', 'Logo de l''entreprise', ?),
            ('image', 'hotspot_image_1', 'Image hotspot 1', ?),
            ('image', 'hotspot_image_2', 'Image hotspot 2', ?),
            ('image', 'hotspot_image_3', 'Image hotspot 3', ?),
            ('image', 'hotspot_image_4', 'Image hotspot 4', ?),
            ('audio', 'welcome_audio', 'Audio de bienvenue (max 800KB)', ?)
        ")->execute([$adminId, $adminId, $adminId, $adminId, $adminId, $adminId]);
    }

    /**
     * GET /api/library
     * Liste tous les médias
     */
    public function index(): void
    {
        $adminId = $this->getAdminId();
        $this->ensureMediaEntries($adminId);
        $media = $this->db->getAllMedia($adminId);

        // Ajouter l'URL complète pour chaque média
        foreach ($media as &$item) {
            if ($item['file_path']) {
                $item['url'] = $this->getMediaUrl($item['file_path']);
            }
        }

        jsonSuccess($media);
    }

    /**
     * GET /api/library/{id}
     * Obtenir un média par ID
     */
    public function show(array $params): void
    {
        $id = (int)$params['id'];
        $adminId = $this->getAdminId();
        $media = $this->db->getMediaById($id, $adminId);

        if (!$media) {
            jsonError(__('api.media_not_found'), 404);
        }

        if ($media['file_path']) {
            $media['url'] = $this->getMediaUrl($media['file_path']);
        }

        jsonSuccess($media);
    }

    /**
     * POST /api/library/{id}/upload
     * Upload un fichier pour un média
     */
    public function upload(array $params): void
    {
        $id = (int)$params['id'];
        $adminId = $this->getAdminId();
        $media = $this->db->getMediaById($id, $adminId);

        if (!$media) {
            jsonError(__('api.media_not_found'), 404);
        }

        // Vérifier si un fichier a été uploadé
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => __('api.upload_err_ini_size'),
                UPLOAD_ERR_FORM_SIZE => __('api.upload_err_form_size'),
                UPLOAD_ERR_PARTIAL => __('api.upload_err_partial'),
                UPLOAD_ERR_NO_FILE => __('api.upload_err_no_file'),
                UPLOAD_ERR_NO_TMP_DIR => __('api.upload_err_no_tmp_dir'),
                UPLOAD_ERR_CANT_WRITE => __('api.upload_err_cant_write'),
            ];
            $error = $_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE;
            jsonError($errorMessages[$error] ?? __('api.upload_error'), 400);
        }

        $file = $_FILES['file'];
        $mimeType = mime_content_type($file['tmp_name']);
        $fileSize = $file['size'];

        // Valider selon le type de média
        if ($media['media_type'] === 'logo' || $media['media_type'] === 'image') {
            if (!in_array($mimeType, $this->allowedImageTypes)) {
                jsonError(__('api.invalid_image_type'), 400);
            }
            if ($fileSize > $this->maxImageSize) {
                jsonError(__('api.image_too_large'), 400);
            }
        } elseif ($media['media_type'] === 'audio') {
            if (!in_array($mimeType, $this->allowedAudioTypes)) {
                jsonError(__('api.invalid_audio_type'), 400);
            }
            if ($fileSize > $this->maxAudioSize) {
                jsonError(__('api.audio_too_large'), 400);
            }
        }

        // Générer un nom de fichier unique
        $extension = $this->getExtensionFromMime($mimeType);
        $filename = $media['media_key'] . '_' . time() . '.' . $extension;
        $filePath = $this->uploadDir . $filename;

        // Supprimer l'ancien fichier s'il existe
        if ($media['file_path'] && file_exists($this->uploadDir . basename($media['file_path']))) {
            unlink($this->uploadDir . basename($media['file_path']));
        }

        // Déplacer le fichier uploadé
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            jsonError(__('api.file_save_failed'), 500);
        }

        // Mettre à jour la base de données
        $this->db->updateMedia($id, [
            'original_name' => $file['name'],
            'file_path' => $filename,
            'file_size' => $fileSize,
            'mime_type' => $mimeType,
        ]);

        $media = $this->db->getMediaById($id, $adminId);
        $media['url'] = $this->getMediaUrl($media['file_path']);

        jsonSuccess($media, __('api.file_uploaded'));
    }

    /**
     * DELETE /api/library/{id}/file
     * Supprimer le fichier d'un média
     */
    public function deleteFile(array $params): void
    {
        $id = (int)$params['id'];
        $adminId = $this->getAdminId();
        $media = $this->db->getMediaById($id, $adminId);

        if (!$media) {
            jsonError(__('api.media_not_found'), 404);
        }

        // Supprimer le fichier physique
        if ($media['file_path']) {
            $fullPath = $this->uploadDir . basename($media['file_path']);
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        }

        // Réinitialiser dans la base de données
        $this->db->clearMedia($id);

        jsonSuccess(null, __('api.file_deleted'));
    }

    /**
     * PUT /api/library/{id}
     * Mettre à jour les métadonnées d'un média
     */
    public function update(array $params): void
    {
        $id = (int)$params['id'];
        $data = getJsonBody();
        $adminId = $this->getAdminId();

        $media = $this->db->getMediaById($id, $adminId);
        if (!$media) {
            jsonError(__('api.media_not_found'), 404);
        }

        // Seule la description peut être modifiée via cette route
        if (isset($data['description'])) {
            $this->db->updateMedia($id, ['description' => $data['description']]);
        }

        $media = $this->db->getMediaById($id, $adminId);
        if ($media['file_path']) {
            $media['url'] = $this->getMediaUrl($media['file_path']);
        }

        jsonSuccess($media, __('api.media_updated'));
    }

    /**
     * GET /api/library/type/{type}
     * Obtenir les médias par type
     */
    public function byType(array $params): void
    {
        $type = $params['type'] ?? '';

        if (!in_array($type, ['logo', 'image', 'audio'])) {
            jsonError(__('api.media_type_invalid'), 400);
        }

        $adminId = $this->getAdminId();
        $media = $this->db->getMediaByType($type, $adminId);

        foreach ($media as &$item) {
            if ($item['file_path']) {
                $item['url'] = $this->getMediaUrl($item['file_path']);
            }
        }

        jsonSuccess($media);
    }

    /**
     * Obtenir l'URL d'un fichier média
     */
    private function getMediaUrl(string $filePath): string
    {
        // Retourne un chemin relatif vers le fichier
        return 'uploads/media/' . basename($filePath);
    }

    /**
     * Obtenir l'extension à partir du type MIME
     */
    private function getExtensionFromMime(string $mimeType): string
    {
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
            'audio/mpeg' => 'mp3',
            'audio/mp3' => 'mp3',
            'audio/wav' => 'wav',
            'audio/ogg' => 'ogg',
            'audio/x-m4a' => 'm4a',
        ];

        return $extensions[$mimeType] ?? 'bin';
    }
}
