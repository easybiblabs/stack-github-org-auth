# Authenticate based on GitHub organization membership

Requires this PR (for the time being): https://github.com/igorw/stack-oauth/pull/9

```php
$stack = (new Stack\Builder())
    ->push('Stack\Session')
    ->push(
        'Igorw\Stack\OAuth',
        [
            'key' => $app['github.key'],
            'secret' => $app['github.secret'],
            'callback_url' => $app['github.callback'],
            'success_url' => $app['url_generator']->generate('homepage'),
            'failure_url'  => '/auth',
            'oauth_service.class' => 'OAuth\OAuth2\Service\GitHub',
            'service_scopes' => [
                'read:org',
            ],
        ]
    )
    ->push(
        'ImagineEasy\Stack\GitHubOrgAuth',
        [
            'organizations' => ['easybiblabs'],
            // the following options are optional
            'github_org_url' => 'https://api.github.com/user/orgs?access_token=' // e.g. for GitHub Enterprise
            'http_client' => new GuzzleHttp\Client,
            'session_orgs' => 'github.orgs', // defines the key used in the session

        ]
    )
;

$app = $stack->resolve($app);

$request = HttpFoundation\Request::createFromGlobals();
$response = $app->handle($request)->send();
$app->terminate($request, $response);
```
