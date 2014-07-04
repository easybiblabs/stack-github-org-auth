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
    private $gitHubOrgUrl = 'https://api.github.com/user/orgs?access_token=';

    private $organizations;

    private $sessionOrgs = 'github.orgs';

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

            $response = new HttpFoundation\Response(
                "Forbidden!",
                HttpFoundation\Response::HTTP_FORBIDDEN
            );
            return $response;

        }

        return $this->app->handle($request, $type, $catch);
    }

    private function extractOrganizations($accessToken)
    {
        $client = new GuzzleHttp\Client();

        $response = $client
            ->get($this->gitHubOrgUrl . $accessToken)
            ->json()
        ;

        $userOrgs = [];
        foreach ($response as $userOrg) {
            $userOrgs[] = $userOrg['login'];
        }

        return $userOrgs;
    }

    private function setUp(array $options)
    {
        if (empty($options['organizations'])) {
            throw new \InvalidArgumentException("Missing 'organizations.'");
        }
        $this->organizations = $options['organizations'];
    }
}
