<?php
declare(strict_types=1);

namespace FreeMagento\Base\Controller\Adminhtml\Extensions;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'FreeMagento_Base::extensions';

    public function __construct(Context $context, private readonly PageFactory $pageFactory)
    {
        parent::__construct($context);
    }

    public function execute()
    {
        $page = $this->pageFactory->create();
        $page->setActiveMenu('FreeMagento_Base::extensions');
        $page->getConfig()->getTitle()->prepend((string)__('FreeMagento extensions'));
        return $page;
    }
}
