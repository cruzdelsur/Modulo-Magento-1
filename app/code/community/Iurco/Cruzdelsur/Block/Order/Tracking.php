
<?php
/**
 *
 *
 */
class Iurco_Cruzdelsur_Block_Order_Tracking extends Mage_Core_Block_Template
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('cruzdelsur/order/tracking.phtml');
    }

    /**
     * Checks and get `?tc=XXXXXX` param from URL
     * @return int
     */
    public function getTrackingNumber()
    {
        $param = Mage::app()->getRequest()->getParam('tc');
        if($param && (strlen($param) >= 3 && strlen($param) <= 9)) {
            return (int) $param;
        }
    }

    public function getValidationCssClasses()
    {
        $classes = array(
            'required-entry'
            ,'validate-number'
            ,'validate-digits'
        );

        return implode(' ', $classes);
    }

}
