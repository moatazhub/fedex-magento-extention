<?php
namespace Fkra\EgyptExpress\Plugin;

class ShipmentBeforeView
{


    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Registry $registry
    ) {
        $this->_coreRegistry = $registry;
    }


    public function beforePushButtons(
        \Magento\Backend\Block\Widget\Button\Toolbar\Interceptor $subject,
        \Magento\Framework\View\Element\AbstractBlock $context,
        \Magento\Backend\Block\Widget\Button\ButtonList $buttonList
    ) {

        $this->_request = $context->getRequest();
        if($this->_request->getFullActionName() == 'adminhtml_order_shipment_view'){
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $egexpress = $objectManager->get("\Fkra\EgyptExpress\Model\Carrier\Egyptexpress");
            $status =   $egexpress->getConfigDataSystem('active');
            if($status == '1'){

                $buttonList->add(
                    'awp_print',
                    [
                        'label' => __('Print AWB'),
                        'class' => 'action-default scalable save primary ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only',
                        'data_attribute' => [
                            'shipment_id' => $this->getShipment()->getId(),
                        ]
                    ],
                    -1
                );
            }
        }

    }



    /**
     * Retrieve shipment model instance
     *
     * @return \Magento\Sales\Model\Order\Shipment
     */
    public function getShipment()
    {
        return $this->_coreRegistry->registry('current_shipment');
    }

}