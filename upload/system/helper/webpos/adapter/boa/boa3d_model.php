<?php
class boa3dModel {

	private $merchantOrderId="";
	private $CustomerId  = "";
	private $MerchantId =""; 
	private $UserName="";
	private $Password="";
	
	
	private $testUrl="https://boatest.kuveytturk.com.tr/boa.virtualpos.services/Home/ThreeDModelPayGate";
	private $testUrl2="https://boatest.kuveytturk.com.tr/boa.virtualpos.services/Home/ThreeDModelProvisionGate";
	
	private $liveUrl="https://boa.kuveytturk.com.tr/sanalposservice/Home/ThreeDModelPayGate";
	private $liveUrl2="https://boa.kuveytturk.com.tr/sanalposservice/Home/ThreeDModelProvisionGate";
	
	private $tamamurl="/index.php?route=payment/webpos/callback";
	private $iptalurl ="/index.php?route=payment/webpos/callback";
	// private $cardnumber="4025903160410013";
	// private $cvv="123";
	// private $month="07";
	// private $year="20";
	// private $MerchantOrderId="DENEME";

	private function createHash($bank) {
		$HashedPassword = base64_encode(sha1($bank['boa_classic_password'],"ISO-8859-9")); //md5($Password);	
        $HashData = base64_encode(sha1($bank['boa_merchant_id'].$bank['order_id'].($bank['total']*100).$bank['success_url'].$bank['fail_url'].$bank['boa_classic_name'].$HashedPassword , "ISO-8859-9"));
		return $HashData;
	}
	
	private function createForm($bank) {
			$response=array();
		$response['message']='';
		$action='';
		if ($bank['mode']=='live') {
			$action=$bank['boa_classic_url'];
		} else if ($bank['mode']=='test') {
			$action=$bank['boa_test_url'];
		}
		$amount=$bank['total']*100;
			
		if($bank['cc_type']==1){ 
			$cardType="VISA";
		} else if($bank['cc_type']==2){
			$cardType="MasterCard";
		}	

		
		$xml_fields=array(
		'OkUrl'=>$bank['success_url'],
		'FailUrl'=>$bank['fail_url'],
		'HashData'=>$this->createHash($bank),
		'MerchantId'=>$bank['boa_merchant_id'],
		'CustomerId'=>$bank['boa_customer_id'],
		'UserName' => $bank['boa_classic_name'],
		'password' => $bank['boa_classic_password'],
		'CardNumber'=>$bank['cc_number'],
		'CardExpireDateYear'=>$bank['cc_expire_date_year'],
		'CardExpireDateMonth'=>$bank['cc_expire_date_month'],
		'CardCVV2' => $bank['cc_cvv2'],
		'CardHolderName'=>$bank['cc_owner'],
		'CardType'=>$cardType,
		//'CardType' => 'Sale',
		'BatchID' => '0',
		'InstallmentCount'=>$bank['instalment'],
		'Amount'=>$amount,
		'DisplayAmount'=>$bank['total'],
		'CurrencyCode' => "0949",
		'MerchantOrderId' => $bank['order_id'],
		'TransactionSecurity'=>"3",
		'TransactionSide'=>"Sale",
		'url'=>$action
		);
			

		$donen = $this->xmlsend($xml_fields);
		
		$form = str_replace('name="downloadForm"','id="webpos_form" name="webpos_form"',$donen);

		return $form;
		
	}
	public function methodResponse($bank){
		$response=array();
		$response['form']=$this->createForm($bank);
		//$response['redirect']=;
		//$response['error']=;
		return $response;
		
	}
	public function bankResponse($bank_response,$bank){
		$AuthenticationResponse=$_POST["AuthenticationResponse"];
		$RequestContent = urldecode($AuthenticationResponse);
		$bank_response=simplexml_load_string($RequestContent) or die("Error: Cannot create object");
		
		$response=array();
		$response['message']='';
						  //<HashData>'.  $bank_response->VPosMessage->HashData.'</HashData>
				//<DisplayAmount>'.(string)$bank_response->VPosMessage->Amount.'</DisplayAmount>				
				//<CurrencyCode>0949</CurrencyCode>

		$APIVersion = "1.0.0";
        $Type = "Sale";    
        $CurrencyCode = "0949"; //TL islemleri için
        $MerchantOrderId = (string)$bank_response->VPosMessage->MerchantOrderId;// Siparis Numarasi
		$Amount = (string)$bank_response->VPosMessage->Amount; //Islem Tutari // örnegin 1.00TL için 100 kati yani 100 yazilmali
        $CustomerId = "";//Müsteri Numarasi
        $MerchantId = ""; //Magaza Kodu
        $OkUrl = $this->tamamurl; //Basarili sonuç alinirsa, yönledirelecek sayfa
        $FailUrl =$this->iptalurl;//Basarisiz sonuç alinirsa, yönledirelecek sayfa
        $UserName=""; // Web Yönetim ekranalrindan olusturulan api rollü kullanici
		$Password="";// Web Yönetim ekranalrindan olusturulan api rollü kullanici sifresi
		$HashedPassword = base64_encode(sha1($Password,"ISO-8859-9")); //md5($Password);	
	    $HashData = base64_encode(sha1($MerchantId.$MerchantOrderId.$Amount.$UserName.$HashedPassword , "ISO-8859-9"));
			if ((string)$bank_response->ResponseCode =="00"){
				$response['message'].='3D Onayı Başarılı.<br/>';
				
				$xml='<KuveytTurkVPosMessage xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
				  <HashData>'.$HashData.'</HashData>
				<MerchantId>'.$MerchantId.'</MerchantId>
				<CustomerId>'.$CustomerId.'</CustomerId>
				<UserName>'.$UserName.'</UserName>
				<TransactionType>Sale</TransactionType>
				<InstallmentCount>'.(string)$bank_response->VPosMessage->InstallmentCount.'</InstallmentCount>
				<Amount>'.$Amount.'</Amount>
				<MerchantOrderId>'.(string)$bank_response->VPosMessage->MerchantOrderId.'</MerchantOrderId>
				<TransactionSecurity>3</TransactionSecurity>
				<KuveytTurkVPosAdditionalData>
				<AdditionalData>
					<Key>MD</Key>
					<Data>'.(string)$bank_response->MD.'</Data>
				</AdditionalData>
			</KuveytTurkVPosAdditionalData>
			</KuveytTurkVPosMessage>';
		


				
				$xml_response=$this->xmlonayla($xml);
				$xml = simplexml_load_string($xml_response);
				
				$ReasonCode=(string)$bank_response->ResponseCode;
				$Response=(string)$bank_response->ResponseMessage;
				
				if($ReasonCode =="00" || $Response === "Approved") {
					$response['result']=1;
					$response['message'].='Ödeme Başarılı<br/>';
					//$response['message'].='AuthCode : '.(string)$xml->Transaction->Response->AuthCode.'<br/>';
					$response['message'].='Response : '.$Response.'<br/>';
				} else {
					$response['result']=0;
					$response['message'].='Ödeme Başarısız.<br/>';
					$response['message'].='Response : '.$Response.'<br/>';
					$response['message'].='ErrMsg : '.(string)$xml->Transaction->Response->SysErrMsg.'<br/>';
					$response['message'].='ErrCode : '.(string)$xml->Transaction->Response->Code.'<br/>';
				}
			
			} else {
				$response['result']=0;
				$response['message'].='3D doğrulama başarısız<br/>';
				$response['message'].=$bank_response['mderrormessage'];
				
			}
		
		//print_r($response);
		return $response;
	}
	private function xmlSend($fields,$odeme=false){
		
			
	
		

		
			$xml= '<KuveytTurkVPosMessage xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">'
				.'<APIVersion>1.0.0</APIVersion>'
				.'<OkUrl>'.$fields['OkUrl'].'</OkUrl>'
				.'<FailUrl>'.$fields['FailUrl'].'</FailUrl>'
				.'<HashData>'.$fields['HashData'].'</HashData>'
				.'<MerchantId>'.$fields['MerchantId'].'</MerchantId>'
				.'<CustomerId>'.$fields['CustomerId'].'</CustomerId>'
				.'<UserName>'.$fields['UserName'].'</UserName>'
				.'<CardNumber>'.$fields['CardNumber'].'</CardNumber>'
				.'<CardExpireDateYear>'.$fields['CardExpireDateYear'].'</CardExpireDateYear>'
				.'<CardExpireDateMonth>'.$fields['CardExpireDateMonth'].'</CardExpireDateMonth>'
				.'<CardCVV2>'.$fields['CardCVV2'].'</CardCVV2>'
				.'<CardHolderName>'.$fields['CardHolderName'].'</CardHolderName>'
				.'<CardType>'.$fields['CardType'].'</CardType>'
				.'<BatchID>0</BatchID>'
				.'<TransactionType>Sale</TransactionType>'
				.'<InstallmentCount>'.$fields['InstallmentCount'].'</InstallmentCount>'
				.'<Amount>'.$fields['Amount'].'</Amount>'
				.'<DisplayAmount>'.$fields['Amount'].'</DisplayAmount>'
				.'<CurrencyCode>'.$fields['CurrencyCode'].'</CurrencyCode>'
				.'<MerchantOrderId>'.$fields['MerchantOrderId'].'</MerchantOrderId>'
				.'<TransactionSecurity>3</TransactionSecurity>'
				.'<TransactionSide>Sale</TransactionSide>'
				.'</KuveytTurkVPosMessage>';
	
				
		 try {
			$ch = curl_init();  
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/xml', 'Content-length: '. strlen($xml)) );
			curl_setopt($ch, CURLOPT_POST, true); //POST Metodu kullanarak verileri gönder  
			curl_setopt($ch, CURLOPT_HEADER, false); //Serverdan gelen Header bilgilerini önemseme.  
			curl_setopt($ch, CURLOPT_URL,$this->liveUrl); //Baglanacagi URL  
			curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
	
		 
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); //Transfer sonuçlarini al.
			$data = curl_exec($ch);  
			curl_close($ch);
		 }
		 catch (Exception $e) {
		 echo 'Caught exception: ',  $e->getMessage(), "\n";
		}
		return $data;
	}
	
	
	
	
	
	private function xmlonayla($xml){
		

				
		 try {
			$ch = curl_init();  
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/xml', 'Content-length: '. strlen($xml)) );
			curl_setopt($ch, CURLOPT_POST, true); //POST Metodu kullanarak verileri gönder  
			curl_setopt($ch, CURLOPT_HEADER, false); //Serverdan gelen Header bilgilerini önemseme.  
			curl_setopt($ch, CURLOPT_URL,$this->liveUrl2); //Baglanacagi URL  
			curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); //Transfer sonuçlarini al.
			$data = curl_exec($ch);  
			curl_close($ch);
		 }
		 catch (Exception $e) {
		 echo 'Caught exception: ',  $e->getMessage(), "\n";
		}
		//echo "<pre>";print_r($data);exit;
		return $data;
	}
	
	
}