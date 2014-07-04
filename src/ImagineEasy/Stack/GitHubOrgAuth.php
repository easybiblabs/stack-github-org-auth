<?php
namespace ImagineEasy\Stack;

use GuzzleHttp;
use Symfony\Component\HttpKernel;
use Symfony\Component\HttpFoundation;

/**
 * GitHubOrgAuth
 *
 * Assumes stack/session and stack/oauth are loaded before.
 *
 * This middleware allows us to authenticate based on a user's GitHub organizations.
 *
 * @package ImagineEasy\Stack
 */
class GitHubOrgAuth implements HttpKernel\HttpKernelInterface
{
    /**
     * @var string
     */
    private $gitHubOrgUrl = 'https://api.github.com/user/orgs?access_token=';

    /**
     * @var GuzzleHttp\Client
     */
    private $httpClient;

    /**
     * @var array
     */
    private $organizations;

    /**
     * @var string
     */
    private $sessionOrgs = 'github.orgs';

    /**
     * @var string See {@link Igorw\Stack\Auth}
     */
    private $sessionToken = 'oauth.token';

    public function __construct(HttpKernel\HttpKernelInterface $app, array $options = [])
    {
        $this->app = $app;
        $this->setUp($options);
    }

    public function handle(HttpFoundation\Request $request, $type = HttpKernel\HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        $token = $request->attributes->get($this->sessionToken);
        if (!$token) {
            return new HttpFoundation\RedirectResponse('/auth');
        }

        $userOrganizations = $request->getSession()->get($this->sessionOrgs);
        if (null === $userOrganizations) {
            $userOrganizations = $this->extractOrganizations($token->getAccessToken());
            $request->getSession()->set($this->sessionOrgs, $userOrganizations);
        }

        if (empty(array_intersect($this->organizations, $userOrganizations))) {
            throw new HttpKernel\Exception\AccessDeniedHttpException();
        }

        return $this->app->handle($request, $type, $catch);
    }

    /**
     * Load the user's organizations from GitHub.
     *
     * @param string $accessToken
     *
     * @return array
     * @throws HttpKernel\Exception\LengthRequiredHttpException
     */
    private function extractOrganizations($accessToken)
    {
        $response = $this->getHttpClient()
            ->get($this->gitHubOrgUrl . $accessToken)
            ->json()
        ;

        $userOrgs = [];
        foreach ($response as $userOrg) {
            $userOrgs[] = $userOrg['login'];
        }

        if (empty($userOrgs)) {
            throw new HttpKernel\Exception\LengthRequiredHttpException("The user has no organizations.");
        }

        return $userOrgs;
    }

    /**
     * @return GuzzleHttp\Client
     */
    private function getHttpClient()
    {
        if (null === $this->httpClient) {
            $this->httpClient = new GuzzleHttp\Client;
        }
        return $this->httpClient;
    }

    /**
     * Set options from {@link self::__construct()}.
     *
     * @param array $options
     *
     * @throws \InvalidArgumentException
     */
    private function setUp(array $options)
    {
        if (empty($options['organizations'])) {
            throw new \InvalidArgumentException("Missing 'organizations.'");
        }
        $this->organizations = $options['organizations'];

        if (!empty($options['github_org_url'])) {
            $this->gitHubOrgUrl = $options['github_org_url'];
        }

        if (isset($options['http_client']) && $options['http_client'] instanceof GuzzleHttp\Client) {
            $this->httpClient = $options['http_client'];
        }

        if (!empty($options['session_orgs'])) {
            $this->sessionOrgs = $options['session_orgs'];
        }
    }
}
