<?php

namespace Richardma\Tuofu\Model;

class Payment extends \Magento\Payment\Model\Method\Cc
{
    const CODE = 'richardma_tuofu';

    protected $_code = self::CODE;
    protected $_isGateway                   = true;
    protected $_canCapture                  = true;
    protected $_canCapturePartial           = false;
    protected $_canRefund                   = false;
    protected $_canRefundInvoicePartial     = false;
    protected $_countryFactory;
    protected $_minAmount = null;
    protected $_maxAmount = null;
    //protected $_supportedCurrencyCodes = array('USD');
    protected $_debugReplacePrivateDataKeys = ['number', 'exp_month', 'exp_year', 'cvc'];
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        //\Stripe\Stripe $stripe,
        array $data = array()
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $moduleList,
            $localeDate,
            null,
            null,
            $data
        );
        $this->_countryFactory = $countryFactory;
        //$this->_stripeApi = $stripe;
        //$this->_stripeApi->setApiKey(
            //$this->getConfigData('api_key')
        //);
        $this->_minAmount = $this->getConfigData('min_order_total');
        $this->_maxAmount = $this->getConfigData('max_order_total');
    }

    protected function _getCurrencyCode($currency) {
        if($currency =='CNY'){
			$currCode='156';
		}else if($currency == 'USD'){
    	    $currCode='840';
		}else if($currency == "GBP"){
		    $currCode='826';
		}else if($currency == "EUR"){
			$currCode='978';
		}else if($currency == "JPY"){
		    $currCode='392';
		}else if($currency == "HKD"){
		    $currCode='344';
		}else if($currency == "AUD"){
			$currCode='036';
		}else if($currency == "CAD"){
			$currCode='124';
		}else if($currency == "NZD"){
			$currCode='554';
		}else if($currency == "DKK"){
			$currCode='208';
		}else if($currency == "INR"){
			$currCode='356';
		}else if($currency == "IDR"){
			$currCode='360';
		}else if($currency == "ILS"){
			$currCode='376';
		}else if($currency == "KRW"){
			$currCode='410';
		}else if($currency == "MOP"){
			$currCode='446';
		}else if($currency == "MYR"){
			$currCode='458';
		}else if($currency == "NOK"){
			$currCode='578';
		}else if($currency == "PHP"){
			$currCode='608';
		}else if($currency == "RUB"){
			$currCode='643';
		}else if($currency == "SGD"){
			$currCode='702';
		}else if($currency == "ZAR"){
			$currCode='710';
		}else if($currency == "SEK"){
			$currCode='752';
		}else if($currency == "CHF"){
			$currCode='756';
		}else if($currency == "TWD"){
			$currCode='901';
		}else if($currency == "TRY"){
			$currCode='949';
		}else if($currency == "MXN"){
			$currCode='484';
		}else if($currency == "BRL"){
			$currCode='986';
		}else if($currency == "ARS"){
			$currCode='032';
		}else if($currency == "PEN"){
			$currCode='604';
		}else if($currency == "CLF"){
			$currCode='990';
		}else if($currency == "COP"){
			$currCode='170';
		}else if($currency == "VEF"){
			$currCode='862';
		}else {
	        $currCode='840';
		}

        return $currCode;
    }

    protected function _getClientIP() {
		if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])){ 
			$online_ip = $_SERVER['HTTP_X_FORWARDED_FOR']; 
		}
		elseif(isset($_SERVER['HTTP_CLIENT_IP'])){ 
			$online_ip = $_SERVER['HTTP_CLIENT_IP']; 
		}
		elseif(isset($_SERVER['HTTP_X_REAL_IP'])){ 
			$online_ip = $_SERVER['HTTP_X_REAL_IP']; 
		}else{ 
			$online_ip = $_SERVER['REMOTE_ADDR']; 
		}
		$ips = explode(",",$online_ip);
		return $ips[0];  
    }

    //货物信息
    protected function _getGoodsInfo($order) {
        $goodsInfo       = '';
        foreach($order->getAllItems() as $item) {
            if(!strstr($goodsInfo,$item->getName())){
                $goodsInfo .= $item->getName()."#,#".$item->getProductId()."#,#".sprintf('%.2f', $item->getPrice())
                           ."#,#".ceil($item->getQtyOrdered())."#;#";
            }
        }

        return $goodsInfo;
    }
	//加密
	protected function _szComputeMD5Hash($input){
		$md5hex=md5($input); 
		$len=strlen($md5hex)/2; 
		$md5raw=""; 
		for($i=0;$i<$len;$i++) { $md5raw=$md5raw . chr(hexdec(substr($md5hex,$i*2,2))); } 
		$keyMd5=base64_encode($md5raw); 
	    return $keyMd5;
	}

    /**
     * Payment capturing
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Validator\Exception
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        //throw new \Magento\Framework\Validator\Exception(__('Inside Stripe, throwing donuts :]'));
        /** @var \Magento\Sales\Model\Order $order */
        $order = $payment->getOrder();
        /** @var \Magento\Sales\Model\Order\Address $billing */
        $billing = $order->getBillingAddress();
        try {
            $requestData = [
                'OrderID'           => $order->getRealOrderId(),
                'CartID'            => $_SESSION['cartId'],
                'CurrCode'          => $this->_getCurrencyCode($order->getOrderCurrencyCode()),
                'Amount'            => $amount * 100, // 以分为单位，不保留小数点
                'CardPAN'           => $payment->getCcNumber(), // 卡号
                'ExpirationMonth'   => sprintf('%02d',$payment->getCcExpMonth()), // 到期月
                'ExpirationYear'    => $payment->getCcExpYear(), // 到期年
                'CVV2'              => $payment->getCcCid(),
                'IPAddress'         => $this->_getClientIP(),
                'CName'             => $billing->getName(),
                'BAdress'           => $billing->getStreetLine(1) . ' ' . $billing->getStreetLine(2),
                'BCity'             => $billing->getCity(),
                'Bstate'            => $billing->getRegion(),
                'Bcountry'          => $billing->getCountry(), // 有没有这个函数？
                'BCountryCode'      => $billing->getCountryId(),
                'PostCode'          => $billing->getPostcode(),
                'Email'             => $order->getCustomerEmail(),
                'Telephone'         => $billing->getTelephone(),
                'Pname'             => $this->_getGoodsInfo($order),
                'IFrame'            => 1,
                'URL'               => $_SERVER["HTTP_HOST"],
                'OrderUrl'          => $_SERVER["HTTP_HOST"],
                'callbackUrl'       => '',
                'Framework'         => 'Magento2',
                'IVersion'          => 'V8.0',
                'Language'          => $_SESSOIN['lang']
            ];

            // 计算加密信息
            $merchantNo = $this->getConfigData('partner_id'); // 后台设置
            $secureCode = $this->getConfigData('security_code'); // 后台设置

            $md5src=$secureCode.$merchantNo.
                $requestData['OrderID'].
                $requestData['Amount'].
                $requestData['CurrCode'];			
            $requestData['HashValue'] = $this->_szComputeMD5Hash($md5src);
            $requestData = http_build_query($requestData, '', '&');

            echo $requestData;
            // 请求信息完成
            
            // 发送请求并判断结果，成功后要获取transaction_id
            // 设置支付状态
            //$payment
                //->setTransactionId($charge->id)
                //->setIsTransactionClosed(0);

            //$requestData = [
                //'amount'        => $amount * 100,
                //'currency'      => strtolower($order->getBaseCurrencyCode()),
                //'description'   => sprintf('#%s, %s', $order->getIncrementId(), $order->getCustomerEmail()),
                //'card'          => [
                    //'number'            => $payment->getCcNumber(),
                    //'exp_month'         => sprintf('%02d',$payment->getCcExpMonth()),
                    //'exp_year'          => $payment->getCcExpYear(),
                    //'cvc'               => $payment->getCcCid(),
                    //'name'              => $billing->getName(),
                    //'address_line1'     => $billing->getStreetLine(1),
                    //'address_line2'     => $billing->getStreetLine(2),
                    //'address_city'      => $billing->getCity(),
                    //'address_zip'       => $billing->getPostcode(),
                    //'address_state'     => $billing->getRegion(),
                    //'address_country'   => $billing->getCountryId(),
                    //// To get full localized country name, use this instead:
                    //// 'address_country'   => $this->_countryFactory->create()->loadByCode($billing->getCountryId())->getName(),
                //]
            //];
            //$charge = \Stripe\Charge::create($requestData);
            //$payment
                //->setTransactionId($charge->id)
                //->setIsTransactionClosed(0);
        } catch (\Exception $e) {
            $this->debugData(['request' => $requestData, 'exception' => $e->getMessage()]);
            $this->_logger->error(__('Payment capturing error.'));
            throw new \Magento\Framework\Validator\Exception(__('Payment capturing error.'));
        }
        return $this;
    }
    /**
     * Payment refund
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Validator\Exception
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $transactionId = $payment->getParentTransactionId();
        try {
            \Stripe\Charge::retrieve($transactionId)->refund(['amount' => $amount * 100]);
        } catch (\Exception $e) {
            $this->debugData(['transaction_id' => $transactionId, 'exception' => $e->getMessage()]);
            $this->_logger->error(__('Payment refunding error.'));
            throw new \Magento\Framework\Validator\Exception(__('Payment refunding error.'));
        }
        $payment
            ->setTransactionId($transactionId . '-' . \Magento\Sales\Model\Order\Payment\Transaction::TYPE_REFUND)
            ->setParentTransactionId($transactionId)
            ->setIsTransactionClosed(1)
            ->setShouldCloseParentTransaction(1);
        return $this;
    }
    /**
     * Determine method availability based on quote amount and config data
     *
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        if ($quote && (
            $quote->getBaseGrandTotal() < $this->_minAmount
            || ($this->_maxAmount && $quote->getBaseGrandTotal() > $this->_maxAmount))
        ) {
            return false;
        }
        if (!$this->getConfigData('api_key')) {
            return false;
        }
        return parent::isAvailable($quote);
    }
    /**
     * Availability for currency
     *
     * @param string $currencyCode
     * @return bool
     */
    public function canUseForCurrency($currencyCode)
    {
        if (!in_array($currencyCode, $this->_supportedCurrencyCodes)) {
            return false;
        }
        return true;
    }
}
