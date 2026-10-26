<?php
declare(strict_types=1);

namespace FreeMagento\Base\Controller\Adminhtml\Extensions;

use FreeMagento\Base\Api\UpdateCheckerInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;

/** "Check now" button: forces a feed refresh. */
class Check extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'FreeMagento_Base::extensions';

    public function __construct(Context $context, private readonly UpdateCheckerInterface $updateChecker)
    {
        parent::__construct($context);
    }

    public function execute()
    {
        $status = $this->updateChecker->check(true);
        $updates = count(array_filter($status, static fn(array $r) => $r['update_available']));
        if ($this->updateChecker->getLastCheckedAt() === null) {
            $this->messageManager->addWarningMessage((string)__('The FreeMagento feed could not be reached. Installed versions are shown below.'));
        } else {
            $this->messageManager->addSuccessMessage($updates
                ? (string)__('%1 update(s) available.', $updates)
                : (string)__('All FreeMagento packages are up to date.'));
        }
        return $this->resultRedirectFactory->create()->setPath('freemagento/extensions/index');
    }
}
