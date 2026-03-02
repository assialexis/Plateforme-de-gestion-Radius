<?php
/**
 * Client API RouterOS pour MikroTik
 * Permet de communiquer avec les routeurs MikroTik via leur API
 */

class RouterOS
{
    private $socket;
    private string $host;
    private int $port;
    private int $timeout;
    private bool $connected = false;
    private bool $debug = false;

    public function __construct(string $host, int $port = 8728, int $timeout = 5)
    {
        $this->host = $host;
        $this->port = $port;
        $this->timeout = $timeout;
    }

    /**
     * Connexion au routeur
     */
    public function connect(string $username, string $password): bool
    {
        $this->socket = @fsockopen($this->host, $this->port, $errno, $errstr, $this->timeout);

        if (!$this->socket) {
            return false;
        }

        stream_set_timeout($this->socket, $this->timeout);

        // Login
        $response = $this->send(['/login', '=name=' . $username, '=password=' . $password]);

        if (isset($response[0]) && $response[0] === '!done') {
            $this->connected = true;
            return true;
        }

        // Ancien style de login (pre-6.43)
        if (isset($response[0]) && $response[0] === '!done' && isset($response[1])) {
            preg_match('/=ret=(.+)/', $response[1], $matches);
            if (isset($matches[1])) {
                $challenge = pack('H*', $matches[1]);
                $hash = md5(chr(0) . $password . $challenge, true);
                $response = $this->send(['/login', '=name=' . $username, '=response=00' . bin2hex($hash)]);

                if (isset($response[0]) && $response[0] === '!done') {
                    $this->connected = true;
                    return true;
                }
            }
        }

        $this->disconnect();
        return false;
    }

    /**
     * Déconnexion
     */
    public function disconnect(): void
    {
        if ($this->socket) {
            fclose($this->socket);
            $this->socket = null;
        }
        $this->connected = false;
    }

    /**
     * Vérifier si connecté
     */
    public function isConnected(): bool
    {
        return $this->connected;
    }

    /**
     * Obtenir les utilisateurs hotspot actifs
     */
    public function getHotspotActive(): array
    {
        if (!$this->connected) {
            return [];
        }

        $response = $this->send(['/ip/hotspot/active/print']);
        return $this->parseResponse($response);
    }

    /**
     * Déconnecter un utilisateur hotspot
     */
    public function hotspotDisconnect(string $id): bool
    {
        if (!$this->connected) {
            return false;
        }

        $response = $this->send(['/ip/hotspot/active/remove', '=.id=' . $id]);
        return isset($response[0]) && $response[0] === '!done';
    }

    /**
     * Déconnecter un utilisateur par username
     * Étape 1: trouver le .id via print, Étape 2: remove par .id
     */
    public function hotspotDisconnectByUser(string $username): bool
    {
        if (!$this->connected) {
            return false;
        }

        // Étape 1: Trouver les sessions actives de cet utilisateur
        $response = $this->send([
            '/ip/hotspot/active/print',
            '=.proplist=.id',
            '?user=' . $username
        ]);

        $entries = $this->parseResponse($response);

        if (empty($entries)) {
            return false;
        }

        // Étape 2: Supprimer chaque session trouvée
        $success = false;
        foreach ($entries as $entry) {
            if (!empty($entry['.id'])) {
                $removeResponse = $this->send([
                    '/ip/hotspot/active/remove',
                    '=.id=' . $entry['.id']
                ]);
                if (isset($removeResponse[0]) && $removeResponse[0] === '!done') {
                    $success = true;
                }
            }
        }

        return $success;
    }

    /**
     * Obtenir les infos du routeur
     */
    public function getIdentity(): ?string
    {
        if (!$this->connected) {
            return null;
        }

        $response = $this->send(['/system/identity/print']);
        $parsed = $this->parseResponse($response);

        return $parsed[0]['name'] ?? null;
    }

    /**
     * Obtenir les ressources système
     */
    public function getResources(): array
    {
        if (!$this->connected) {
            return [];
        }

        $response = $this->send(['/system/resource/print']);
        $parsed = $this->parseResponse($response);

        return $parsed[0] ?? [];
    }

    /**
     * Envoyer une commande et recevoir la réponse
     */
    private function send(array $command): array
    {
        foreach ($command as $word) {
            $this->writeWord($word);
        }
        $this->writeWord('');

        return $this->readResponse();
    }

    /**
     * Écrire un mot dans le socket
     */
    private function writeWord(string $word): void
    {
        $len = strlen($word);

        if ($len < 0x80) {
            fwrite($this->socket, chr($len));
        } elseif ($len < 0x4000) {
            $len |= 0x8000;
            fwrite($this->socket, chr(($len >> 8) & 0xFF) . chr($len & 0xFF));
        } elseif ($len < 0x200000) {
            $len |= 0xC00000;
            fwrite($this->socket, chr(($len >> 16) & 0xFF) . chr(($len >> 8) & 0xFF) . chr($len & 0xFF));
        } elseif ($len < 0x10000000) {
            $len |= 0xE0000000;
            fwrite($this->socket, chr(($len >> 24) & 0xFF) . chr(($len >> 16) & 0xFF) . chr(($len >> 8) & 0xFF) . chr($len & 0xFF));
        } else {
            fwrite($this->socket, chr(0xF0) . chr(($len >> 24) & 0xFF) . chr(($len >> 16) & 0xFF) . chr(($len >> 8) & 0xFF) . chr($len & 0xFF));
        }

        fwrite($this->socket, $word);
    }

    /**
     * Lire un mot depuis le socket
     */
    private function readWord(): ?string
    {
        $byte = fread($this->socket, 1);
        if ($byte === false || $byte === '') {
            return null;
        }

        $len = ord($byte);

        if ($len >= 0xF0) {
            $len = ord(fread($this->socket, 1));
            $len = ($len << 8) + ord(fread($this->socket, 1));
            $len = ($len << 8) + ord(fread($this->socket, 1));
            $len = ($len << 8) + ord(fread($this->socket, 1));
        } elseif ($len >= 0xE0) {
            $len = $len & 0x1F;
            $len = ($len << 8) + ord(fread($this->socket, 1));
            $len = ($len << 8) + ord(fread($this->socket, 1));
            $len = ($len << 8) + ord(fread($this->socket, 1));
        } elseif ($len >= 0xC0) {
            $len = $len & 0x3F;
            $len = ($len << 8) + ord(fread($this->socket, 1));
            $len = ($len << 8) + ord(fread($this->socket, 1));
        } elseif ($len >= 0x80) {
            $len = $len & 0x7F;
            $len = ($len << 8) + ord(fread($this->socket, 1));
        }

        if ($len === 0) {
            return '';
        }

        $word = '';
        while (strlen($word) < $len) {
            $chunk = fread($this->socket, $len - strlen($word));
            if ($chunk === false) {
                break;
            }
            $word .= $chunk;
        }

        return $word;
    }

    /**
     * Lire la réponse complète
     * Note: dans le protocole MikroTik, un mot vide (len=0) est un séparateur
     * de phrase, PAS la fin de la réponse. Seuls !done/!trap/!fatal terminent.
     */
    private function readResponse(): array
    {
        $response = [];

        while (true) {
            $word = $this->readWord();

            if ($word === null) {
                break; // Connexion perdue
            }

            if ($word === '') {
                continue; // Séparateur de phrase - continuer la lecture
            }

            $response[] = $word;

            if ($word === '!done' || $word === '!trap' || $word === '!fatal') {
                // Lire les mots restants de ce bloc final
                while (($extra = $this->readWord()) !== null && $extra !== '') {
                    $response[] = $extra;
                }
                break;
            }
        }

        return $response;
    }

    /**
     * Parser la réponse en tableau associatif
     */
    private function parseResponse(array $response): array
    {
        $result = [];
        $current = [];

        foreach ($response as $word) {
            if ($word === '!re') {
                if (!empty($current)) {
                    $result[] = $current;
                }
                $current = [];
            } elseif ($word === '!done' || $word === '!trap') {
                if (!empty($current)) {
                    $result[] = $current;
                }
                break;
            } elseif (strpos($word, '=') === 0) {
                $parts = explode('=', substr($word, 1), 2);
                if (count($parts) === 2) {
                    $current[$parts[0]] = $parts[1];
                }
            }
        }

        // Sécurité: flush le dernier record si !done n'a pas été reçu
        if (!empty($current)) {
            $result[] = $current;
        }

        return $result;
    }

    /**
     * Activer le mode debug
     */
    public function setDebug(bool $debug): void
    {
        $this->debug = $debug;
    }
}
