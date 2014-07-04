# Authorize based on GitHub organization membership

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
        ]
    )
;

$app = $stack->resolve($app);

$request = HttpFoundation\Request::createFromGlobals();
$response = $app->handle($request)->send();
$app->terminate($request, $response);
```
