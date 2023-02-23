<?php

namespace Unleash\Tests\Repository;
use Unleash\Exception\HttpResponseException;
use Unleash\Tests\AbstractHttpClientTest;
class DefaultUnleashRepositoryTest extends AbstractHttpClientTest
{
    private $response = [
        'version' => 1,
        'features' => [
            [
                'name' => 'test',
                'description' => '',
                'enabled' => true,
                'strategies' => [
                    [
                        'name' => 'flexibleRollout',
                        'parameters' => [
                            'groupId' => 'default',
                            'rollout' => '99',
                            'stickiness' => 'DEFAULT',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'test2',
                'description' => '',
                'enabled' => true,
                'strategies' => [
                    [
                        'name' => 'userWithId',
                        'parameters' => [
                            'userIds' => 'test,test2',
                        ],
                    ],
                ],
            ],
        ],
    ];

    public function testFindFeature()
    {
        $this->pushResponse($this->response, 3);
        self::assertEquals('test', $this->repository->findFeature('test')->getName());
        self::assertEquals('test2', $this->repository->findFeature('test2')->getName());
        self::assertNull($this->repository->findFeature('test3'));
    }

    public function testGetFeatures()
    {
        $this->pushResponse($this->response, 2);
        self::assertCount(2, $this->repository->getFeatures());

        $features = $this->repository->getFeatures();
        self::assertEquals('test', $features[array_keys($features)[0]]->getName());
        self::assertEquals('flexibleRollout', $features[array_keys($features)[0]]->getStrategies()[0]->getName());
        self::assertEquals('test2', $features[array_keys($features)[1]]->getName());
        self::assertEquals('userWithId', $features[array_keys($features)[1]]->getStrategies()[0]->getName());

        $this->pushResponse([], 1, 401);
        $this->expectException(HttpResponseException::class);
        $this->repository->getFeatures();
    }
}
