<?php

namespace Gov2lib;

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\GenericProvider;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Core document class for managing page rendering, body content,
 * components, templates, sidebars and error handling.
 *
 * @author Wibisono Sastrodiwiryo <wibi@alumni.ui.ac.id>
 * @since 2009-11-22
 * @version 5.0 - PHP 8.4 refactor
 */
#[\AllowDynamicProperties]
class document extends customException
{
    /** @var array<string, mixed> Template body data */
    public array $body = [];

    /** @var array<int, string>|null Content blocks */
    public ?array $content = null;

    /** @var array<int, string>|null Right content blocks */
    public ?array $contentRight = null;

    /** @var array<int, string>|null Left content blocks */
    public ?array $contentLeft = null;

    /** @var array<int, string>|null Sidebar blocks */
    public ?array $sidebar = null;

    /** @var array<int, string>|null Right sidebar blocks */
    public ?array $sidebarRight = null;

    /** @var array<string, string>|null Error collection */
    public ?array $error = null;

    /** @var array<int, string>|null External JS snippets */
    public ?array $externalJS = null;

    /** @var int Internal counter for content ordering */
    public int $counter = 0;

    /** @var string Base body template file */
    public string $baseBody = '';

    /** @var string Current page ID */
    public string $pageID = '';

    /** @var string Template directory path */
    public string $templateDir = '';

    /** @var string Class name identifier */
    public string $className = '';

    /** @var array|null Collected menu items */
    public ?array $collectMenu = null;

    /** @var string Controller file path */
    public string $controller = '';

    /** @var string Component name */
    public string $componentName = '';

    /** @var gov2session|null Session handler */
    public ?gov2session $ses = null;

    /** @var gov2option|null Options handler */
    public ?gov2option $opt = null;

    /** @var gov2survey|null Survey handler */
    public ?gov2survey $sur = null;

    public function __construct()
    {
        global $config;

        $this->body = [];
        $this->body['_SERVER'] = $_SERVER;
        $this->body['webroot'] = $config->webroot ?? '';
        $this->body['protocol'] = $config->protocol ?? '';
        $this->body['ssonode'] = $config->platform->ssonode ?? '';
    }

    /**
     * Load all model classes from an app directory.
     */
    public function takeAll(string $appDir): void
    {
        $dir = __DIR__ . "/../../apps/{$appDir}/model";

        if (file_exists($dir)) {
            $files = array_slice(scandir($dir), 2);
            foreach ($files as $val) {
                $this->take($appDir, str_replace('.php', '', $val));
            }
        }
    }

    /**
     * Instantiate and attach a model class from an app module.
     */
    public function take(
        string $appDir,
        string $class = '',
        string $fn = '',
        mixed $param = '',
        string $dsn = ''
    ): void {
        global $loader, $config;

        if (!$class) {
            $class = $appDir;
        }

        $handler = "\\App\\{$appDir}\\model\\{$class}";

        try {
            $this->component($appDir);

            if (class_exists($handler)) {
                if (!$dsn) {
                    $dsn = (string) ($config->domain->attr['dsn'] ?? '');
                }

                $this->{$class} = new $handler($dsn);
                $loader->addPath($this->{$class}->templateDir, $appDir);

                if ($fn && !$param) {
                    if (method_exists($this->{$class}, $fn)) {
                        $this->{$class}->{$fn}();
                    } else {
                        throw new \Exception("FunctionNotExist: {$handler}\\{$fn}()");
                    }
                } elseif ($fn && $param) {
                    $this->{$class}->{$fn}($param);
                }
            } else {
                throw new \Exception("Class/NameSpaceNotExist: {$handler}");
            }
        } catch (\Exception $e) {
            $this->exceptionHandler($e->getMessage());
        }
    }

    /**
     * Register a content template block.
     */
    public function content(string $contentFile = '', string $side = ''): void
    {
        global $doc, $self;

        $caller = explode('\\', get_called_class());
        $callerClass = $caller[3] ?? '';
        $template = '';

        if ($callerClass && isset($self->{$callerClass})) {
            $template = $self->{$callerClass}->templateDir ?? '';
        }

        if (!$template) {
            $template = $self->templateDir ?? '';
        }

        if (!$contentFile && file_exists("{$template}/{$callerClass}.html")) {
            $contentFile = "{$callerClass}.html";
        } elseif (!$contentFile && file_exists("{$template}/index.html")) {
            $contentFile = 'index.html';
        }

        $contentName = match ($side) {
            'right' => 'contentRight',
            'left' => 'contentLeft',
            default => 'content',
        };

        if (!isset($doc->{$contentName})) {
            $doc->{$contentName} = [];
        }

        $doc->counter();

        $callerModule = $caller[1] ?? '';
        if ($callerModule !== 'document' && !str_contains($callerModule, 'Handler')) {
            $contentFile = "@{$callerModule}/{$contentFile}";
        }

        $doc->{$contentName}[$doc->counter] = $contentFile;
    }

    /**
     * Register a sidebar template block.
     */
    public function sidebar(string $sidebarFile): void
    {
        global $doc;

        $caller = explode('\\', get_called_class());

        if (!isset($this->sidebar)) {
            $this->sidebar = [];
        }

        $doc->counter();

        if (($caller[1] ?? '') !== 'document') {
            $sidebarFile = "@{$caller[1]}/{$sidebarFile}";
        }

        $doc->sidebar[$doc->counter] = $sidebarFile;
    }

    /**
     * Register a right sidebar template block.
     */
    public function sidebarRight(string $sidebarFile): void
    {
        global $doc;

        $caller = explode('\\', get_called_class());

        if (!isset($this->sidebarRight)) {
            $this->sidebarRight = [];
        }

        $doc->counter();

        if (($caller[1] ?? '') !== 'document') {
            $sidebarFile = "@{$caller[1]}/{$sidebarFile}";
        }

        $doc->sidebarRight[$doc->counter] = $sidebarFile;
    }

    /**
     * Set a body template variable.
     */
    public function body(string $var, mixed $val): void
    {
        $this->body[$var] = $val;
    }

    /**
     * Increment the internal content counter.
     */
    public function counter(): void
    {
        $this->counter++;
    }

    /**
     * Merge an array of variables into the body data.
     */
    public function vars(array $vars): void
    {
        foreach ($vars as $key => $val) {
            $this->body[$key] = $val;
        }
    }

    /**
     * Scan an app directory for Vue components and register them.
     */
    public function component(string $appDir): void
    {
        global $doc, $pageID;

        // Skip if components for this app are already registered
        if (!empty($doc->body['components'][$appDir])) {
            return;
        }

        $dir = __DIR__ . "/../../apps/{$appDir}/vue";
        $components = [];

        if (file_exists($dir)) {
            $files = array_slice(scandir($dir), 2);

            foreach ($files as $key => $val) {
                if (str_ends_with($val, '.vue') && !str_starts_with($val, '_')) {
                    $components[$key] = [
                        'component' => $val,
                        'tag' => str_replace('.vue', '', $val),
                        'pageID' => $appDir,
                    ];
                }
            }

            $doc->body['components'][$appDir] = $components;
        }
    }

    /**
     * Register an error.
     */
    public function error(string $code, string $message): void
    {
        if (!isset($this->error)) {
            $this->error = [];
        }

        $this->error[$code] = $message;
    }

    /**
     * Build a response array for POST operations.
     */
    public function response(string $class, string $callback = '', int $id = 0): array
    {
        global $vars, $doc;

        $message = '';

        if (is_array($doc->error)) {
            foreach ($doc->error as $key => $val) {
                $message .= "{$key}: {$val}\n";
            }
        } else {
            $cmd = strtoupper($_POST['cmd'] ?? $vars['cmd'] ?? '');
            $message = "Operasi {$cmd} berhasil";

            if ($id) {
                $message .= " dengan nomor ID {$id}";
            }
        }

        $response = [
            'class' => $class,
            'notification' => $message,
            'callback' => $callback,
            'id' => $id,
        ];

        if (!empty($_POST['parent_id'])) {
            $response['parent_id'] = $_POST['parent_id'];
        }

        return $response;
    }

    /**
     * Build a GET response, handling errors.
     */
    public function responseGet(mixed $data): mixed
    {
        global $doc;

        if (!$doc->error) {
            return $data;
        }

        header('HTTP/1.1 422 Query Fails');
        return $doc->response('is-danger', 'openErr');
    }

    /**
     * Build an authentication response, handling errors.
     */
    public function responseAuth(mixed $data = null): mixed
    {
        global $doc, $self, $pageID;

        if (!$doc->error) {
            return $data;
        }

        $response = $doc->response('is-danger', 'openSnackbar');
        $response['server'] = $_SERVER['SERVER_NAME'];
        $response['app'] = $pageID;
        $response['endpoint'] = $self->className ?? '';
        header('HTTP/1.1 401 Unauthorized');

        return $response;
    }

    /**
     * Render Vue.js data into the template body.
     */
    public function renderJS(): void
    {
        global $vueData, $vueCreated, $vueMethods, $vueWatch;

        if (is_array($vueData ?? null)) {
            $this->body('vueData', json_encode($vueData));
        }

        $this->body('vueCreated', $vueCreated ?? '');
        $this->body('vueWatch', $vueWatch ?? '');
        $this->body('vueMethods', $vueMethods ?? '');
        $this->body('externalJS', $this->externalJS);
    }

    /**
     * Render the final page output using Twig.
     */
    public function render(): void
    {
        global $twig, $template, $doc, $self, $loader, $config, $pageID;

        $this->body('sidebars', $this->sidebar);
        $this->body('sidebarsRight', $this->sidebarRight);

        // Load top-bar options from database
        if (isset($self) && isset($self->opt) && $pageID) {
            try {
                $this->body('_OPTIONS', $self->opt->getAll($pageID));
            } catch (\Exception $e) {
                $this->body('_OPTIONS', []);
            }
        }


        if (is_array($this->error)) {
            $this->body('pageTitle', 'Exception Occured');
            $this->body('subTitle', 'Please check exception list below ');
            $this->body('errors', $this->error);

            $errorBody = $this->buildErrorBody($config, $self, $loader);
            $this->body('contents', $errorBody);
        } else {
            $this->body('contents', $this->content);
            $this->body('contentsRight', $this->contentRight);
            $this->body('contentsLeft', $this->contentLeft);
        }

        $this->renderJS();
        $template = $twig->load($doc->baseBody);
        echo $template->render($this->body);
    }

    /**
     * Build the error body template list based on error type.
     */
    private function buildErrorBody(mixed $config, mixed $self, mixed $loader): array
    {
        $errorBody = [];

        $loader->addPath(__DIR__ . '/../../apps/components/view', 'components');

        if (isset($this->error['NotLogin'])) {
            $errorBody[] = 'errorMessage.html';
        } else {
            $errorBody[] = '@components/gov2navBreadcrumb.html';
            $errorBody[] = 'errorMessage.html';
        }

        $errorBody[] = '@components/gov2notification.html';

        if (isset($this->error['ErrToken'])) {
            $errorBody[] = 'tokenMan.html';
        }

        if (isset($this->error['NoSession'])) {
            $errorBody[] = 'tokenForm.html';
        }

        if (isset($this->error['NotLogin'])) {
            $errorBody = $this->handleNotLoginError($errorBody, $config, $self, $loader);
        }

        return $errorBody;
    }

    /**
     * Handle the NotLogin error case, including Keycloak redirect.
     */
    private function handleNotLoginError(
        array $errorBody,
        mixed $config,
        mixed $self,
        mixed $loader
    ): array {
        $keycloak = $config->keycloak ?? null;
        $isGov2 = true;

        if (isset($_GET['type']) && $_GET['type'] === 'gov2') {
            $isGov2 = true;
        }

        if ($keycloak && (int) ($keycloak->active ?? 0) && !$isGov2) {
            $this->redirectToKeycloak($config, $self);
        } else {
            $loader->addPath(__DIR__ . '/../../apps/gov2login/view', 'gov2login');
            $errorBody[] = '@gov2login/notLogin.html';
            $this->baseBody = '@gov2login/body.html';
        }

        $_SESSION['ssonode'] = trim((string) ($config->platform->ssonode ?? ''));

        return $errorBody;
    }

    /**
     * Initiate Keycloak OAuth2 redirect.
     */
    private function redirectToKeycloak(mixed $config, mixed $self): void
    {
        $realm = (string) ($config->domain->attr['realm'] ?? '');
        $options = [
            'clientId' => (string) ($config->domain->attr['clientId'] ?? ''),
            'clientSecret' => (string) ($config->domain->attr['clientSecret'] ?? ''),
            'urlAuthorize' => str_replace('{realm}', $realm, (string) ($config->keycloak->urlAuthorize ?? '')),
            'urlAccessToken' => str_replace('{realm}', $realm, (string) ($config->keycloak->urlAccessToken ?? '')),
            'urlResourceOwnerDetails' => str_replace('{realm}', $realm, (string) ($config->keycloak->urlResourceOwnerDetails ?? '')),
        ];

        $provider = new GenericProvider($options);

        if (!isset($self->ses->val['account_id'])) {
            try {
                $authorizationUrl = $provider->getAuthorizationUrl();
                $_SESSION['oauth2state'] = $provider->getState();

                $GLOBALS['vueData']['href'] = $authorizationUrl;
                $GLOBALS['vueCreated'] .= 'vm = this;
                    eventBus.$emit("openNotif",
                        {class: "info",
                            callback:"infoSnackbar",
                            notification: "Page akan di redirect ke login page KeyCloak dalam 2 detik"});
                    setTimeout(function(){
                        vm.$window.location.href = vm.href;
                    }, 2000)
                ';
            } catch (IdentityProviderException $e) {
                $this->exceptionHandler($e->getMessage());
            }
        } else {
            $expiredIn = $self->ses->val['expired_in'] ?? '';
            if (date($expiredIn) < date('Y-m-d H:i:s')) {
                $self->take('gov2login', 'login');
                try {
                    $self->login->createKeycloakSession($provider, 'refresh_token');
                } catch (IdentityProviderException $e) {
                    $this->exceptionHandler($e->getMessage());
                }
            }
        }
    }

    /**
     * Register an external JavaScript snippet.
     */
    public function externalJS(string $codeSnippet): void
    {
        global $doc;

        $caller = explode('\\', get_called_class());

        if (!isset($this->externalJS)) {
            $this->externalJS = [];
        }

        $doc->counter();

        if (($caller[1] ?? '') !== 'document') {
            $codeSnippet = "@{$caller[1]}/{$codeSnippet}";
        }

        $doc->externalJS[$doc->counter] = $codeSnippet;
    }

    /**
     * Decode a JWT-encoded environment value.
     */
    public function envRead(?string $data = null): array
    {
        global $publickey;

        if (empty($data)) {
            return [];
        }

        $result = JWT::decode($data, new Key($publickey, 'HS256'));

        return json_decode(json_encode($result), true);
    }
}
