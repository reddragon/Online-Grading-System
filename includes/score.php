<?php

# score = score * 0.2^(submit_time/total_time) * 90^(no_of_resubmits)

#-------------------------------------------------------------------------------
# Calculates the score after $elapsed seconds given $initial score.

function score_calc($initial, $elapsed_secs, $total_secs) {
	global $cfg;

	$score = $initial * pow($cfg['score']['base'], $elapsed_secs*1.0/$total_secs);
	if ($_SESSION['user_id'] == 1) {
        echo $elapsed_secs, "/", $total_secs, ": ", pow($cfg['score']['base'], $elapsed_secs*1.0/$total_secs); 
	}
	return $score;
}

#-------------------------------------------------------------------------------
# Applies penalty to a given score, for a given no. of resubmits
# NOTE: resubmits = submits - 1 for final evaluation

function score_resubmit_penalty($initial, $resubmits) {
    global $cfg;
    
    $score = $initial * pow((100-$cfg['score']['resubmit_penalty'])/100.0, $resubmits);
    return $score;
}

#--------------------------------------------------------------------------------
# Modifies the ratings & volatilites of all contestants in a contest based on
# their scores in the contest.

function score_rate($contest_id) {
	global $cfg;
    $scores = array();
    $ratings[] = array();
    $vols = array();
    $matches = array();
    //To be Modified
    $link=mysql_connect($cfg["db"]["hostspec"],$cfg["db"]["username"],$cfg["db"]["password"]);
    if(!$link)
    {
      echo "error conn db";
    }
    $t=mysql_select_db($cfg["db"]["database"],$link);
    if(!$t)
    {
      echo "error conn db1";
    }
    
    $res = mysql_query('SELECT * FROM members where contest_id = '.$contest_id,$link);
    $cnt=0;
    while ($user=mysql_fetch_array($res)) {
      $res1 = mysql_query('SELECT score FROM teams where team_id = '.$user['team_id'],$link);
      $user1=mysql_fetch_array($res1);
      $res2 = mysql_query('SELECT * FROM users WHERE user_id = '.$user['user_id'],$link);
      $user2=mysql_fetch_array($res2);
      $res3=mysql_query('SELECT * from teams where contest_id='.$contest_id." order by score desc",$link);
      $res4=mysql_query('SELECT * from members,users where users.user_id=members.user_id and members.contest_id='.$contest_id.' order by users.rating desc',$link);
      $exp=0;
      $cur=0;
      while($t=mysql_fetch_array($res3))
      {
	  if($t['score']==$user1['score'])
	  break;
	  $cur++;
      }
      
      while($t=mysql_fetch_array($res4))
      {
	  if($t['rating']<$user2['rating'])
	  break;
	  $exp++;
      }
      $exp--;
      $exp1=0;
$res4=mysql_query('SELECT * from members,users where users.user_id=members.user_id and members.contest_id='.$contest_id.' order by users.rating desc',$link);
      while($t=mysql_fetch_array($res4))
      {
	  if($t['rating']==$user2['rating'])
	  break;
	  $exp1++;
      }
      //echo $user2['handle']." ".$exp." ".$exp1." ";      
      $exp=($exp1+$exp)/2;
      //echo $exp." ";
      
      $res5=mysql_query('SELECT COUNT(*) as count FROM teams WHERE contest_id='.$contest_id,$link);
      $user5=mysql_fetch_array($res5);
      
      $nrat=score_rating_calc($user2['rating'],$cur,$exp,$user5['count']);
      //echo $nrat." ";
      $ratings[$cnt]=$nrat;
      $cnt++;
      
      
      
      
      
   }
    $i=0;
$res = mysql_query('SELECT * FROM members where contest_id = '.$contest_id,$link);
   while($user=mysql_fetch_array($res))
    {
      mysql_query('UPDATE users SET rating='.$ratings[$i].' WHERE users.user_id='.$user['user_id'],$link);
      $i++;
    }
}

#--------------------------------------------------------------------------------
# $sc = scores[]
# $rat = ratings[]
# $vol = volatilities[]
# $mat = no. of matches[]
function score_rating_calc($currat,$cur,$exp,$n)
{
    $nrat=$currat+($exp-$cur)*$n*0.74;
    return $nrat;
}
  
function score_rating_calc2($sc, $rat, $vol, $mat) {
    echo $sc." ".$rat." ".$vol." ".$mat;
    $mm=0;
    $vm=0;
    $mr=0;
    $vr=0;
    $i=0;
    $n=$sc;
    $E = 2.71828;
   echo "hi"; 
    for($i=0;$i<$n;$i++)
    {
        $mm+=$sc[$i];
        $mr+=$rat[$i];
    }
    $mm/=$n;
    $mr/=$n;
    for($i=0;$i<$n;$i++)
    {
        $vm+=($sc[$i]-$mm)*($sc[$i]-$mm);
        $vr+=($rat[$i]-$mr)*($rat[$i]-$mr);
        $vr+=pow($vol[$i],1+pow($E,-$mat[$i]/20.0));
    }
    $vm/=$n;
    $vr/=$n;
    
    $expec=array();
    $guard=0;
    for($i=0;$i<$n;$i++)
    {
        $w1=1.2;
        $w3=1.44;
        array_push($expec, $w1*($sc[$i]-$mm)*sqrt($vr/$vm)+$mr);
        $expec[$i]=$rat[$i]*pow($E,-$vol[$i]/50)+$expec[$i]*(1-pow($E,-$vol[$i]/50));
        $cap=100+750.0/($mat[$i]+1);
        $w2=1.8;
        if(abs($expec[$i]-$rat[$i])>$cap) {
            if ($expec[$i]>$rat[$i]) {
                $expec[$i]=$rat[$i]+$cap;
            } else {
                $expec[$i]=$rat[$i]-$cap;
            }
        }
        $guard=min($guard,$expec[$i]);
        $vol[$i]=sqrt((($expec[$i]-$rat[$i])*($expec[$i]-$rat[$i])/$w3+ $vol[$i]*$vol[$i]/$w2));
    }
    
    for($i=0;$i<$n;$i++) {
        $expec[$i]-=$guard;
    }
    
    $rat = $expec;
    //echo $rat;
  return $rat;
}

?>
