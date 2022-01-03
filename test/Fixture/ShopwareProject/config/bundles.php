<?php

declare(strict_types=1);

$result = [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    Symfony\Bundle\MonologBundle\MonologBundle::class => ['all' => true],
    Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle::class => ['all' => true],
    Symfony\Bundle\TwigBundle\TwigBundle::class => ['all' => true],
    Symfony\Bundle\DebugBundle\DebugBundle::class => ['dev' => true, 'test' => true],
    Enqueue\Bundle\EnqueueBundle::class => ['all' => true],
    Enqueue\MessengerAdapter\Bundle\EnqueueAdapterBundle::class => ['all' => true],
    Shopware\Core\Framework\Framework::class => ['all' => true],
    Shopware\Core\System\System::class => ['all' => true],
    Shopware\Core\Content\Content::class => ['all' => true],
    Shopware\Core\Checkout\Checkout::class => ['all' => true],
    Shopware\Core\Profiling\Profiling::class => ['dev' => true],
    Shopware\Storefront\Storefront::class => ['all' => true],
    \Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Fixture\Bundle::class => ['all' => true],
];

// support shopware: <6.4
if (\class_exists(Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle::class)) {
    $result[Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle::class] = ['all' => true];
}

return $result;
