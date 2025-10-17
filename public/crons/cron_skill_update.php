<?php

chdir("..");
require_once("include/common.inc");
require_once("lib/common.lib");
require_once("lib/skill.lib");
require_once("lib/user_stat.lib");

define('CRON_CLEANUP_SKILLS_PORTION', 1000);
define('CRON_CLEANUP_SKILLS_DELAY', 100000);
define('CRON_CLEANUP_OTHER_PORTION', 1000);
define('CRON_CLEANUP_OTHER_DELAY', 50000);

foreach ($NODE_NUMS as $nn) {
	NODE_SWITCH($nn);
	
	PF_CALL('skills time delete');
	$skill_ids = get_hash(skill_list(false,sql_pholder(' AND flags & ?#SKILL_FLAG_TEMP'), 'id'),'id','id');
	if($skill_ids){
        while (common_delete($db,TABLE_USER_SKILLS,array('skill_id'=>$skill_ids),sql_pholder(' AND value < ?  LIMIT ?#CRON_CLEANUP_SKILLS_PORTION',time_current()))) {
            usleep(CRON_CLEANUP_SKILLS_DELAY);
        }
	}
	PF_RET(false,'skills time delete');
	
	PF_CALL('gifts count delete');
	while(user_stat_delete(array('type_id' => USER_STAT_TYPE_MISC, 'object_id' => USER_STAT_OBJECT_GIFTS_SENDED), sql_pholder(' LIMIT ?#CRON_CLEANUP_OTHER_PORTION'))) {
		usleep(CRON_CLEANUP_OTHER_DELAY);
	}
	PF_RET(false,'gifts count delete');

}

require_once("lib/fight_planner.lib");
fight_planner_cron();
?>
