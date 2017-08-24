<?php
/**
 * Dhl Shipping
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to
 * newer versions in the future.
 *
 * PHP version 7
 *
 * @category  Dhl
 * @package   Dhl\Shipping
 * @author    Sebastian Ertner <sebastian.ertner@netresearch.de>
 * @copyright 2017 Netresearch GmbH & Co. KG
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      http://www.netresearch.de/
 */
namespace Dhl\Shipping\Block\Adminhtml\Order\Shipment;

use Dhl\Shipping\Config\BcsConfigInterface;
use Dhl\Shipping\Model\Config\ServiceConfig;
use Dhl\Shipping\Model\Config\ModuleConfigInterface;
use Dhl\Shipping\Model\Shipping\Carrier;
use Dhl\Shipping\Service\Filter\ProductFilter;
use Magento\Framework\DataObject;
use Magento\Framework\DataObjectFactory;

/**
 * Services
 *
 * @category Dhl
 * @package  Dhl\Shipping
 * @author   Sebastian Ertner <sebastian.ertner@netresearch.de>
 * @license  http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link     http://www.netresearch.de/
 */
class Services extends \Magento\Backend\Block\Template
{
    const BCS_SERVICES_TEMPLATE = 'Dhl_Shipping::order/packaging/popup_services_bcs.phtml';

    const GL_SERVICES_TEMPLATE = '';

    /**
     * @var ModuleConfigInterface
     */
    private $moduleConfig;

    /**
     * @var \Magento\Framework\Registry
     */
    private $coreRegistry;

    /**
     * @var ServiceConfig
     */
    private $serviceConfig;

    /**
     * @var BcsConfigInterface
     */
    private $bcsConfig;

    /**
     * @var \Magento\Shipping\Model\CarrierFactory
     */
    private $carrierFactory;

    /**
     * @var DataObjectFactory
     */
    private $dataObjectFactory;

    /**
     * Services constructor.
     *
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Backend\Block\Template\Context $context
     * @param ModuleConfigInterface $moduleConfig
     * @param ServiceConfig $serviceConfig
     * @param BcsConfigInterface $bcsConfig
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Registry $registry,
        \Magento\Backend\Block\Template\Context $context,
        ModuleConfigInterface $moduleConfig,
        ServiceConfig $serviceConfig,
        BcsConfigInterface $bcsConfig,
        \Magento\Shipping\Model\CarrierFactory $carrierFactory,
        DataObjectFactory $dataObjectFactory,
        array $data = []
    ) {
        $this->coreRegistry = $registry;
        $this->moduleConfig = $moduleConfig;
        $this->serviceConfig = $serviceConfig;
        $this->bcsConfig = $bcsConfig;
        $this->carrierFactory = $carrierFactory;
        $this->dataObjectFactory = $dataObjectFactory;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve shipment model instance
     *
     * @return \Magento\Sales\Model\Order\Shipment
     */
    public function getShipment()
    {
        return $this->coreRegistry->registry('current_shipment');
    }

    /**
     *
     * @return string
     */
    public function getTemplate()
    {
        $bcsCountries = ['DE', 'AT'];
        $usedTemplate = self::BCS_SERVICES_TEMPLATE;
        $originCountryId = $this->moduleConfig->getShipperCountry($this->getShipment()->getStoreId());

        if (!in_array($originCountryId, $bcsCountries)) {
            $usedTemplate = self::GL_SERVICES_TEMPLATE;
        }

        return $usedTemplate;
    }

    /**
     * @return \Dhl\Shipping\Service\ServiceCollection
     */
    public function getServiceCollection()
    {
        $shipment = $this->getShipment();
        $carrierCode = $shipment->getOrder()->getShippingMethod(true)->getData('carrier_code');
        $carrier = $this->carrierFactory->create($carrierCode, $shipment->getStoreId());
        $shipperCountry = $this->moduleConfig->getShipperCountry($shipment->getStoreId());
        $destCountryId = $this->getShipment()->getShippingAddress()->getCountryId();

        $params = $this->dataObjectFactory->create([
            'data' => [
                'country_shipper' => $shipperCountry,
                'country_recipient' => $destCountryId
            ]
        ]);

        $containerType = current(array_keys($carrier->getContainerTypes($params)));
        $productFilter = ProductFilter::create($containerType);
        $serviceCollection = $this->serviceConfig
            ->getServices($this->getShipment()->getStoreId())
            ->filter($productFilter);

        return $serviceCollection;
    }
}
