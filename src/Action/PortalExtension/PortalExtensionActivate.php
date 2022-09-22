<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalExtension;

use Heptacom\HeptaConnect\Portal\Base\Portal\PortalExtensionType;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalExtension\Activate\PortalExtensionActivatePayload;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalExtension\Activate\PortalExtensionActivateResult;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalExtension\PortalExtensionActivateActionInterface;

final class PortalExtensionActivate extends PortalExtensionSwitchActive implements PortalExtensionActivateActionInterface
{
    public function activate(PortalExtensionActivatePayload $payload): PortalExtensionActivateResult
    {
        $payloadExtensions = $payload->getExtensions();

        $pass = $this->toggle($payload->getPortalNodeKey()->withoutAlias(), $payloadExtensions);
        $fail = $payloadExtensions->filter(
            static fn (PortalExtensionType $type): bool => !$pass->contains($type)
        );

        return new PortalExtensionActivateResult($pass, $fail);
    }

    protected function getTargetActiveState(): int
    {
        return 1;
    }
}
