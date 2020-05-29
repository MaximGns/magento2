<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Widget\Controller\Adminhtml\Widget\Instance;

use Magento\Framework\App\Action\HttpPostActionInterface;

class Validate extends \Magento\Widget\Controller\Adminhtml\Widget\Instance implements HttpPostActionInterface
{
    /**
     * Validate action
     *
     * @return void
     */
    public function execute()
    {
        $response = new \Magento\Framework\DataObject();
        $response->setError(false);
        $widgetInstance = $this->_initWidgetInstance();
        $result = $widgetInstance->validate();
        if ($result !== true && (is_string($result) || $result instanceof \Magento\Framework\Phrase)) {
            $this->messageManager->addError((string) $result);
            $this->_view->getLayout()->initMessages();
            $response->setError(true);
            $response->setHtmlMessage($this->_view->getLayout()->getMessagesBlock()->getGroupedHtml());
        }
        $response = $response->toJson();
        $this->_translateInline->processResponseBody($response);
        $this->_response->representJson($response);
    }
}
