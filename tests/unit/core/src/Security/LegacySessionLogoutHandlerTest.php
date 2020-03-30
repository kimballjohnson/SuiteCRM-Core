<?php namespace App\Tests;

use App\Security\LegacySessionLogoutHandler;
use AspectMock\Test;
use AuthenticationController;
use Codeception\Test\Unit;
use Exception;
use SuiteCRM\Core\Legacy\Authentication;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Logout\SessionLogoutHandler;

class LegacySessionLogoutHandlerTest extends Unit
{
    /**
     * @var \App\Tests\UnitTester
     */
    protected $tester;

    /**
     * @var LegacySessionLogoutHandler
     */
    protected $handler;

    /**
     * @var bool
     */
    protected $decoratedCalled = false;

    /**
     * @var bool
     */
    public $logoutCalled = false;

    /**
     * @var bool
     */
    public $initCalled = false;

    /**
     * @var bool
     */
    public $closeCalled = false;

    /**
     * @throws Exception
     */
    protected function _before()
    {
        $projectDir = codecept_root_dir();
        $legacyDir = $projectDir . '/legacy';
        $legacySessionName = 'LEGACYSESSID';
        $defaultSessionName = 'PHPSESSID';

        $self = $this;

        test::double(Authentication::class, [
            'getAuthenticationController' => function () use ($self) {

                return $self->make(
                    AuthenticationController::class,
                    [
                        'logout' => static function (bool $redirect = true) use ($self) {
                            $self->logoutCalled = true;

                            return true;
                        }
                    ]
                );
            },
            'init' => function () use ($self) {
                $self->initCalled = true;
            },
            'close' => function () use ($self) {
                $self->closeCalled = true;
            }
        ]);

        /** @var SessionLogoutHandler $sessionLogoutHandler */
        $sessionLogoutHandler = $self->make(
            SessionLogoutHandler::class,
            [
                'logout' => static function (Request $request, Response $response, TokenInterface $token) use ($self) {
                    $self->decoratedCalled = true;

                    return;
                }
            ]
        );

        $originalHandler = new Authentication($projectDir, $legacyDir, $legacySessionName, $defaultSessionName);

        $this->handler = new LegacySessionLogoutHandler($sessionLogoutHandler, $originalHandler);
    }

    protected function _after()
    {
        $this->logoutCalled = false;
        $this->initCalled = false;
        $this->closeCalled = false;
        $this->decoratedCalled = false;
    }

    // tests

    /**
     * Test that legacy logout is called
     * @throws Exception
     */
    public function testLegacyLogoutCalled(): void
    {
        $request = new Request();
        $response = new Response();
        $token = $this->makeEmpty(TokenInterface::class, []);
        $this->handler->logout($request, $response, $token);

        static::assertTrue($this->logoutCalled);
        static::assertTrue($this->initCalled);
        static::assertTrue($this->logoutCalled);
        static::assertTrue($this->decoratedCalled);
    }
}