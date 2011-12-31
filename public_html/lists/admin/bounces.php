<?php

require_once dirname(__FILE__).'/accesscheck.php';

$result = '';

if (isset($_REQUEST['delete']) && $_REQUEST['delete']) {
  # delete the index in delete
  $result .= $GLOBALS['I18N']->get('deleting').' '.$_REQUEST['delete']."..\n";
  if ($GLOBALS["require_login"] && !isSuperUser()) {
  } else {
    deleteBounce($_REQUEST['delete']);
  }
  $result .= $GLOBALS['I18N']->get('done');
  print ActionResult($result);
}

if (isset($_GET['action']) && $_GET['action']) {
  switch($_GET['action']) {
    case "deleteunidentified":
      Sql_Query(sprintf('delete from %s where status = "unidentified bounce" and `date` < date_sub(now(),interval 2 month)',$tables["bounce"]));
      break;
    case "deleteprocessed":
      Sql_Query(sprintf('delete from %s where comment != "not processed" and `date` < date_sub(now(),interval 2 month)',$tables["bounce"]));
      break;
    case "deleteall":
      Sql_Query(sprintf('delete from %s',$tables["bounce"]));
      break;
    case "reset":
      Sql_Query(sprintf('update %s set bouncecount = 0',$tables["user"]));
      Sql_Query(sprintf('update %s set bouncecount = 0',$tables["message"]));
      Sql_Query(sprintf('delete from %s',$tables["bounce"]));
      Sql_Query(sprintf('delete from %s',$tables["user_message_bounce"]));
   }
}

# view bounces
$count = Sql_Query(sprintf('select count(*) from %s',$tables["bounce"]));
$totalres = Sql_fetch_Row($count);
$total = $totalres[0];
$find_url = '';
if (isset($_GET['start'])) {
  $start = sprintf('%d',$_GET['start']);
} else {
  $start = 0;
}
$offset = $start;
$baseurl = "bounces&amp;start=$start";

if ($total > MAX_USER_PP) {
  $limit = MAX_USER_PP;

  $paging = simplePaging("bounces",$start,$total,MAX_USER_PP,$GLOBALS['I18N']->get('bounces') );
  $query = sprintf("select * from %s where status != ? order by date desc limit $limit offset $offset", $tables['bounce']);
  $result = Sql_Query_Params($query, array('unidentified bounce'));
} else {
  $query = sprintf('select * from %s where status != ? order by date desc', $tables['bounce']);
  $result = Sql_Query_Params($query, array('unidentified bounce'));
}

print '<div class="actions">';
print PageLinkButton('listbounces',$GLOBALS['I18N']->get('view bounces by list'));
  $buttons = new ButtonGroup(new Button(PageURL2("bounces"),'delete'));
  $buttons->addButton(
    new ConfirmButton(
      $GLOBALS['I18N']->get('are you sure you want to delete all unidentified bounces older than 2 months') . "?",
      PageURL2("$baseurl&amp;action=deleteunidentified"),
      $GLOBALS['I18N']->get('delete all unidentified (&gt; 2 months old)')));
  $buttons->addButton(
    new ConfirmButton(
      $GLOBALS['I18N']->get('are you sure you want to delete all bounces older than 2 months') . "?",
      PageURL2("$baseurl&amp;action=deleteprocessed"),
      $GLOBALS['I18N']->get('delete all processed (&gt; 2 months old)')));
  $buttons->addButton(
    new ConfirmButton(
      $GLOBALS['I18N']->get('are you sure you want to delete all bounces') . "?",
      PageURL2("$baseurl&amp;action=deleteall"),
      $GLOBALS['I18N']->get('Delete all')));
 if (ALLOW_DELETEBOUNCE) {
  print $buttons->show();
}
print '</div>';

if (!Sql_Num_Rows($result)) {
  print '<p class="information">' . $GLOBALS['I18N']->get('no unprocessed bounces available') . "</p>";
}

$ls = new WebblerListing($GLOBALS['I18N']->get('bounces'));
$ls->usePanel($paging);
#print '<table class="bouncesListing"><tr><td></td><td>' . $GLOBALS['I18N']->get('message') . "</td><td>" . $GLOBALS['I18N']->get('user') . "</td><td>" . $GLOBALS['I18N']->get('date') . "</td></tr>";
while ($bounce = Sql_fetch_array($result)) {
#@@@ not sure about these ones - bounced list message
  $element = $bounce["id"];
  $ls->addElement($element,PageUrl2("bounce&amp;id=".$bounce["id"]));
  if (preg_match("#bounced list message ([\d]+)#",$bounce["status"],$regs)) {
    $messageid = sprintf('<a href="./?page=message&amp;id=%d">%d</a>',$regs[1],$regs[1]);
  } elseif ($bounce["status"] == "bounced system message") {
    $messageid = $GLOBALS['I18N']->get('System Message');
  } else {
    $messageid = $GLOBALS['I18N']->get('Unknown');
  }
  $ls->addColumn($element,$GLOBALS['I18N']->get('message'),$messageid);
  if (preg_match("#([\d]+) bouncecount increased#",$bounce["comment"],$regs)) {
    $userid = sprintf('<a href="./?page=user&amp;id=%d">%d</a>',$regs[1],$regs[1]);
  } elseif (preg_match("#([\d]+) marked unconfirmed#",$bounce["comment"],$regs)) {
    $userid = sprintf('<a href="./?page=user&amp;id=%d">%d</a>',$regs[1],$regs[1]);
  } else {
    $userid = $GLOBALS['I18N']->get('Unknown');
  }
  $ls->addColumn($element,$GLOBALS['I18N']->get('user'),$userid);
  $ls->addColumn($element,$GLOBALS['I18N']->get('date'),$bounce["date"]);

/*
  printf( "<tr><td>[ <a href=\"javascript:deleteRec('%s');\">%s</a> |
   %s ] </td><td>%s</td><td>%s</td><td>%s</td></tr>\n",
   PageURL2("bounces",$GLOBALS['I18N']->get('delete'),"s=$start&amp;delete=".$bounce["id"]),
   $GLOBALS['I18N']->get('delete'),
   PageLinkButton("bounce",$GLOBALS['I18N']->get('Show'),"s=$start&amp;id=".$bounce["id"]),
   $messageid,
   $userid,
   $bounce["date"]
   );
*/
}
#print "</table>";
print $ls->display();
?>
