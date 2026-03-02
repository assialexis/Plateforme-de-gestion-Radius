<?php
/**
 * Routeur API simple
 */

class Router
{
    private array $routes = [];
    private array $middlewares = [];
    private string $basePath = '';

    public function __construct(string $basePath = '')
    {
        $this->basePath = rtrim($basePath, '/');
    }

    /**
     * Ajouter une route GET
     */
    public function get(string $path, callable $handler): self
    {
        return $this->addRoute('GET', $path, $handler);
    }

    /**
     * Ajouter une route POST
     */
    public function post(string $path, callable $handler): self
    {
        return $this->addRoute('POST', $path, $handler);
    }

    /**
     * Ajouter une route PUT
     */
    public function put(string $path, callable $handler): self
    {
        return $this->addRoute('PUT', $path, $handler);
    }

    /**
     * Ajouter une route DELETE
     */
    public function delete(string $path, callable $handler): self
    {
        return $this->addRoute('DELETE', $path, $handler);
    }

    /**
     * Ajouter un groupe de routes
     */
    public function group(string $prefix, callable $callback): self
    {
        $previousBasePath = $this->basePath;
        $this->basePath .= $prefix;
        $callback($this);
        $this->basePath = $previousBasePath;
        return $this;
    }

    /**
     * Ajouter un middleware global
     */
    public function middleware(callable $middleware): self
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    /**
     * Ajouter une route
     */
    private function addRoute(string $method, string $path, callable $handler): self
    {
        $fullPath = $this->basePath . $path;
        // Convertir les paramètres {id} en regex
        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $fullPath);
        $pattern = '#^' . $pattern . '$#';

        $this->routes[] = [
            'method' => $method,
            'path' => $fullPath,
            'pattern' => $pattern,
            'handler' => $handler
        ];

        return $this;
    }

    /**
     * Dispatcher la requête
     */
    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];

        // Priorité 1: paramètre route (via .htaccess rewrite)
        if (!empty($_GET['route'])) {
            $uri = $_GET['route'];
        }
        // Priorité 2: PATH_INFO (certains serveurs)
        elseif (!empty($_SERVER['PATH_INFO'])) {
            $uri = $_SERVER['PATH_INFO'];
        }
        // Priorité 3: Parser REQUEST_URI
        else {
            $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $scriptName = $_SERVER['SCRIPT_NAME'];

            // Retirer le chemin du script si présent
            if (strpos($uri, $scriptName) === 0) {
                $uri = substr($uri, strlen($scriptName));
            } else {
                $scriptDir = dirname($scriptName);
                if ($scriptDir !== '/' && strpos($uri, $scriptDir) === 0) {
                    $uri = substr($uri, strlen($scriptDir));
                }
            }
        }

        // S'assurer que l'URI commence par /
        $uri = '/' . trim($uri, '/');

        // Support de PUT/DELETE via _method
        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }

        // Exécuter les middlewares globaux
        foreach ($this->middlewares as $middleware) {
            $result = $middleware();
            if ($result === false) {
                return;
            }
        }

        // Chercher la route correspondante
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $uri, $matches)) {
                // Extraire les paramètres
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                try {
                    $handler = $route['handler'];
                    $handler($params);
                } catch (Exception $e) {
                    $this->handleError($e);
                }
                return;
            }
        }

        // Route non trouvée
        $this->notFound();
    }

    /**
     * Gérer une erreur
     */
    private function handleError(Exception $e): void
    {
        $rawCode = $e->getCode();
        $code = is_int($rawCode) && $rawCode >= 400 && $rawCode < 600 ? $rawCode : 500;
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }

    /**
     * Route non trouvée
     */
    private function notFound(): void
    {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Route not found'
        ]);
    }
}
