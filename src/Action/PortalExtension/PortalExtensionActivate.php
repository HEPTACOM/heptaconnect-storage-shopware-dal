<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalExtension;

use Heptacom\HeptaConnect\Storage\Base\Action\PortalExtension\Activate\PortalExtensionActivatePayload;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalExtension\Activate\PortalExtensionActivateResult;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalExtension\PortalExtensionActivateActionInterface;

final class PortalExtensionActivate extends PortalExtensionSwitchActive implements PortalExtensionActivateActionInterface
{
    public function activate(PortalExtensionActivatePayload $payload): PortalExtensionActivateResult
    {
        $payloadExtensions = $payload->getExtensions();

        $pass = $this->toggle($payload->getPortalNodeKey()->withoutAlias(), $payloadExtensions);
        $fail = \array_diff($payloadExtensions, $pass);

        return new PortalExtensionActivateResult($pass, $fail);
    }

    protected function getTargetActiveState(): int
    {
        return 1;
    }
}
