<?php
//
// FogBugz Case Events Web Hook
// =============================================================================
//
// Post case opening events from FogBugz to a Slack chat room.
//
// Author: [Craig Davis](craig@there4development.com)
//
// -----------------------------------------------------------------------------
//
class semaphore extends SlackServicePlugin
{
  public $name = "Semaphore Build Status";
  public $desc = "Process SemaphoreApp.com build status notifications and forward them on to Slack";

  public $cfg = array(
    'has_token' => true,
  );

  public function onInit() {
    $channels = $this->getChannelsList();
    foreach ($channels as $channel => $name) {
      if ($name == '#general') {
        $this->icfg['channel'     ] = $channel;
        $this->icfg['channel_name'] = $name;
      }
    }
    $this->icfg['botname']      = 'Semaphore';
    $this->icfg['icon_url']     = trim($GLOBALS['cfg']['root_url'], '/') . '/plugins/semaphore/icon_48.png';
  }

  public function onView() {
    return $this->smarty->fetch('view.html');
  }

  public function getLabel() {
    return "Post build statuses to {$this->icfg['channel_name']} as {$this->icfg['botname']}";
  }

  public function onEdit() {
    var_dump($this->icfg);
    $channels = $this->getChannelsList();

    if ($_GET['save']) {
      $this->icfg['channel']       = $_POST['channel'];
      $this->icfg['channel_name']  = $channels[$_POST['channel']];
      $this->icfg['botname']       = $_POST['botname'];
      $this->icfg['icon_url']      = $_POST['icon_url'];
      $this->saveConfig();

      header("location: {$this->getViewUrl()}&saved=1");
      exit;
    }

    $this->smarty->assign('channels', $channels);
    return $this->smarty->fetch('edit.html');
  }

  public function onHook($req) {
    $req = json_decode($req['post']['payload'], true);
    $chatMessage = '';
    $resultEmoji = $req['result'] === 'failed' ? ':x:' : ':white_check_mark';

    $chatMessage .= $this->escapeText($resultEmoji);
    $chatMessage .= $this->escapeText($req['result'] . ': ');
    $chatMessage .= $this->escapeText($req['message']);
    $chatMessage .= $this->escapeText(' - ' . $req['author_name'] . ' (');
    $chatMessage .= $this->escapeLink($req['build_url']);
    $chatMessage .= $this->escapeText(')');

    $logMessage = sprintf('Posted build status %d', $req['build_url']);
    $this->sendMessage($chatMessage);

    return $logMessage;
  }

  private function sendMessage($text) {
    $ret = $this->postToChannel($text, array(
      'channel'  => $this->icfg['channel'],
      'username' => $this->icfg['botname'],
      'icon_url' => $this->icfg['icon_url'],
    ));

    return array(
      'ok'     => true,
      'status' => 'Sent a message',
    );
  }
}

/* End of file plugin.php */
