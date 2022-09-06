<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalExtension;

use Heptacom\HeptaConnect\Portal\Base\Portal\PortalExtensionType;
use Heptacom\HeptaConnect\Portal\Base\Portal\PortalExtensionTypeCollection;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalExtension\Deactivate\PortalExtensionDeactivatePayload;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalExtension\Deactivate\PortalExtensionDeactivateResult;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalExtension\PortalExtensionDeactivateActionInterface;

final class PortalExtensionDeactivate extends PortalExtensionSwitchActive implements PortalExtensionDeactivateActionInterface
{
    public function deactivate(PortalExtensionDeactivatePayload $payload): PortalExtensionDeactivateResult
    {
        $payloadExtensions = $payload->getExtensions();

        $pass = $this->toggle($payload->getPortalNodeKey()->withoutAlias(), $payloadExtensions);
        $fail = $payloadExtensions->filter(
            static fn (PortalExtensionType $type): bool => !$pass->contains($type)
        );

        return new PortalExtensionDeactivateResult($pass, $fail);
    }

    protected function getTargetActiveState(): int
    {
        return 0;
    }
}
