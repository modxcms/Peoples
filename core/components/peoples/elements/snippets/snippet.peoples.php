<?php
/**
 * Displays a list of Users
 */
$peoples = $modx->getService('peoples','Peoples',$modx->getOption('peoples.core_path',null,$modx->getOption('core_path').'components/peoples/').'model/peoples/',$scriptProperties);
if (!($peoples instanceof Peoples)) return '';
$output = '';

/* setup default properties */
$tpl = $modx->getOption('tpl',$scriptProperties,'pplUser');
$limit = $modx->getOption('limit',$scriptProperties,10);
$active = $modx->getOption('active',$scriptProperties,true);
$usergroups = $modx->getOption('usergroups',$scriptProperties,'');
$start = $modx->getOption('start',$scriptProperties,0);
$sortBy = $modx->getOption('sortBy',$scriptProperties,'username');
$sortDir = $modx->getOption('sortDir',$scriptProperties,'ASC');
$cls = $modx->getOption('cls',$scriptProperties,'ppl-user');
$altCls = $modx->getOption('altCls',$scriptProperties,'ppl-user-alt');
$firstCls = $modx->getOption('firstCls',$scriptProperties,'');
$lastCls = $modx->getOption('lastCls',$scriptProperties,'');
$placeholderPrefix = $modx->getOption('placeholderPrefix',$scriptProperties,'peoples.');
$profileAlias = $modx->getOption('profileAlias',$scriptProperties,'Profile');

/* build query */
$c = $modx->newQuery('modUser');
if (is_bool($active) || $active < 2) {
    $c->where(array(
        'modUser.active' => $active,
    ));
}
/* filter by user groups */
if (!empty($usergroups)) {
    $c->leftJoin('modUserGroupMember','UserGroupMembers');
    $c->leftJoin('modUserGroup','UserGroup','UserGroupMembers.user_group = UserGroup.id');
    $c->where(array(
        'UserGroup.name:IN' => explode(',',$usergroups),
    ));
}
$count = $modx->getCount('modUser',$c);
$c->sortby($sortBy,$sortDir);
if (!empty($limit)) {
    $c->limit($limit,$start);
}
$c->bindGraph('{"'.$profileAlias.'":{}}');
$users = $modx->getCollectionGraph('modUser','{"'.$profileAlias.'":{}}',$c);
$c->prepare();
echo $c->toSql();

/* iterate */
$list = array();
$alt = false;
$iterativeCount = count($users);
$idx = 0;
foreach ($users as $user) {
    if (empty($user->Profile)) continue;
    
    $userArray = $user->get(array('id','username','active','class_key','remote_key','remote_data'));
    $userArray = array_merge($user->Profile->toArray(),$userArray);

    /* get extended data */
    $extended = $user->Profile->get('extended');
    if (!empty($extended)) {
        $userArray = array_merge($extended,$userArray);
    }
    
    /* get remote data */
    $remoteData = $user->get('remote_data');
    if (!empty($remoteData)) {
        $userArray = array_merge($remoteData,$userArray);
    }
    

    $userArray['cls'] = $alt ? $altCls : $cls;
    if (!empty($firstCls) && $idx == 0) {
        $userArray['cls'] .= ' '.$firstCls;
    }
    if (!empty($lastCls) && $idx == $iterativeCount-1) {
        $userArray['cls'] .= ' '.$lastCls;
    }
    $list[] = $peoples->getChunk($tpl,$userArray);
    $alt = !$alt;
    $idx++;
}

/* set total placeholders */
$placeholders = array(
    'total' => $count,
    'start' => $start,
    'limit' => $limit,
);
$modx->setPlaceholders($placeholders,$placeholderPrefix);


/* output */
$outputSeparator = $modx->getOption('outputSeparator',$scriptProperties,"\n");
$output = implode($list,$outputSeparator);
$toPlaceholder = $modx->getOption('toPlaceholder',$scriptProperties,false);
if (!empty($toPlaceholder)) {
    $modx->setPlaceholder($toPlaceholder,$output);
    return '';
}
return $output;