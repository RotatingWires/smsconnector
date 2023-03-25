<?php
namespace FreePBX\modules\Sms\Adaptor;
use PDO;
class Smsconnector extends \FreePBX\modules\Sms\AdaptorBase {
    /**
	* Create the Adaptor\Smsconnector Class statically while checking to make sure the class hasn't already been loaded
	*
	* @param object Database Object
	* @return object Adaptor\Smsconnector object
	*/
	static function &create() {
		static $obj;
		if (!isset($obj)) {
			$obj = new Smsconnector();
		}
		return $obj;
	}

    /**
     * Extend AdaptorBase send/receive methods with our specific cases
     */

    public function sendMedia($to,$from,$cnam,$message=null,$files=array(),$time=null,$adaptor=null,$emid=null,$chatId='') {
        // store in database
        $retval = array();
        try {
            $retval['id'] = parent::sendMedia($to, $from, $cnam, $message, $files, $time, 'Smsconnector', $emid, $chatId);
            $retval['status'] = true;
        } catch (\Exception $e) {
            throw new \Exception('Unable to store media: '.$e->getmessage());
        }
        
        // look up provider info containing name and api credentials
        $provider     = $this->lookUpProvider($from);
        $providerInfo = $this->FreePBX->Smsconnector->getProvider($provider['name']);
        if (!empty($providerInfo))
        {
            if (! empty($providerInfo['class']))
            {
                $providerInfo['class']->sendMedia($provider, $retval['id'], $to, $from, $message);
            }
        }  
        return $retval;
    }

    public function sendMessage($to,$from,$cnam,$message,$time=null,$adaptor=null,$emid=null,$chatId='') {
        // store in database
        $retval = array();
        try {
            $retval['id'] = parent::sendMessage($to, $from, $cnam, $message, $time, 'Smsconnector', $emid, $chatId);
            $retval['status'] = true;
        } catch (\Exception $e) {
            throw new \Exception('Unable to store message: '.$e->getMessage());
        }
    
        // look up provider info containing name and api credentials
        $provider = $this->lookUpProvider($from);

        $providerInfo = $this->FreePBX->Smsconnector->getProvider($provider['name']);
        if (!empty($providerInfo))
        {
            if (! empty($providerInfo['class']))
            {
                $providerInfo['class']->sendMessage($provider, $retval['id'], $to, $from, $message);
            }
        }
        return $retval;
    }

    public function getMessage($to,$from,$cnam,$message,$time=null,$adaptor=null,$emid=null) {
        return parent::getMessage($to, $from, $cnam, $message, $time, 'Smsconnector', $emid);
    }

    public function updateMessageByEMID($emid,$message,$adaptor=null) {
        return parent::updateMessageByEMID($emid, $message, 'Smsconnector');
    }

    /**
	 * Looks up provider info given DID
	 * 
	 * @param string $did 
	 * @return array hash containing provider details
	 */
	private function lookUpProvider($did)
	{
		$sql = 'SELECT p.name, p.api_key, p.api_secret FROM smsconnector_providers AS p INNER JOIN ' .
				'smsconnector_relations AS r ON p.id = r.providerid ' .
				'INNER JOIN sms_dids AS d ON r.didid = d.id ' .
				'WHERE d.did = :did';
		$stmt = $this->db->prepare($sql);
		$stmt->bindParam(':did', $did, \PDO::PARAM_STR);
		$stmt->execute();
		$row = $stmt->fetchObject();
		return array( 
            'name' => $row->name,
            'api_key' => $row->api_key,
            'api_secret' => $row->api_secret
        );
	}
}
