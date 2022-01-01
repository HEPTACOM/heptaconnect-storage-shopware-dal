<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalExtension;

use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalExtension\Deactivate\PortalExtensionDeactivateActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalExtension\Deactivate\PortalExtensionDeactivatePayload;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalExtension\Deactivate\PortalExtensionDeactivateResult;

class PortalExtensionDeactivate extends PortalExtensionSwitchActive implements PortalExtensionDeactivateActionInterface
{
    public function deactivate(PortalExtensionDeactivatePayload $payload): PortalExtensionDeactivateResult
    {
        $payloadExtensions = $payload->getExtensions();

        $pass = $this->toggle($payload->getPortalNodeKey(), $payloadExtensions);
        $fail = \array_diff($payloadExtensions, $pass);

        return new PortalExtensionDeactivateResult($pass, $fail);
    }

    protected function getTargetActiveState(): int
    {
        return 0;
    }
}
