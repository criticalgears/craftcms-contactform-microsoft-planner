<?php
namespace criticalgears\contactformmicrosoftplanner;
use craft\contactform\events\SendEvent;
use craft\contactform\Mailer;
use criticalgears\contactformmicrosoftplanner\models\SettingsModel;
use GuzzleHttp\Client;
use yii\base\Event;

/**
 * CraftCMS contact form to microsoft planner
 * Version 1.0.1
 */



class Plugin extends \craft\base\Plugin
{
    const VERSION = '1.0.1';

    public $hasCpSettings=true;

    public function init()
    {

        Event::on(Mailer::class, Mailer::EVENT_BEFORE_SEND, function (SendEvent $e) {
            $settings = $this->getSettings();
            $submission = $e->submission;

            $sendData = [

                'name' => $submission->fromName,
                'email' => $submission->fromEmail,
                'subject' => '',
                'message' => '',
                'phone' => '',

                'custom1' => '',
                'custom2' => '',

                'wesbite' => $this->getSiteURL(),
                'ip' => $_SERVER['REMOTE_ADDR'],
                'form' => $settings->formName,
            ];

            $submissionMessage = $submission->message;
            if(is_array($submissionMessage)){
                if(isset($submissionMessage['body'])) {
                    $sendData['message'] = $submissionMessage['body'];
                    unset($submissionMessage['body']);
                }

                if(isset($submissionMessage['fromPhone'])) {
                    $sendData['phone'] = $submissionMessage['fromPhone'];
                    unset($submissionMessage['fromPhone']);
                }

                if(isset($submissionMessage['subject'])) {
                    $sendData['subject'] = $submissionMessage['subject'];
                    unset($submissionMessage['subject']);
                }

                /*
                 * Collecting custom fields
                 */
                if(count($submissionMessage)){
                    $i=1;
                    foreach ($submissionMessage as $k=>$v){
                        if($i>2)
                            break;
                        $sendData['custom'.$i] = $v;
                       $i++;
                    }
                }
            }else{
                $sendData['message'] = $submissionMessage;

            }

            $client = new Client();
            $options = [
                'json' => $sendData,
            ];

            $res = $client->request('POST', $settings->apiURL, $options);
            $statusCode = $res->getStatusCode();
        });
    }

    protected function createSettingsModel()
    {
        return new SettingsModel();
    }

    protected function settingsHtml()
    {
        return \Craft::$app->getView()->renderTemplate('contactform-microsoft-planner/_settings',[
            'settings'=>$this->getSettings()
        ]);
    }
    /**
     * Get site home URL
     * @return string
     */
    public function getSiteURL(){
        $baseURL = \Craft::$app->sites->primarySite->baseUrl;
        if($baseURL === '$DEFAULT_SITE_URL'){
            $baseURL = \Craft::parseEnv($baseURL);
        }
        return $baseURL;
    }
}
