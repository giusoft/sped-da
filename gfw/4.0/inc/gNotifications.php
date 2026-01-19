<?

use RMS\PushNotificationsBundle\Message\iOSMessage;

class PushDemoController extends Controller
{
    public function pushAction()
    {
        $message = new iOSMessage();
        $message->setMessage('Oh my! A push notification!');
        $message->setDeviceIdentifier('test012fasdf482asdfd63f6d7bc6d4293aedd5fb448fe505eb4asdfef8595a7');

        $this->container->get('rms_push_notifications')->send($message);

        return new Response('Push notification send!');
    }
}
?>
