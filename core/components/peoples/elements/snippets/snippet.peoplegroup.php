<?php
/**
 * PeopleGroups
 *
 * Copyright 2010 by Shaun McCormick <shaun@modx.com>
 *
 * Peoples is free software; you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the License, or (at your option) any later
 * version.
 *
 * Peoples is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * Peoples; if not, write to the Free Software Foundation, Inc., 59 Temple
 * Place, Suite 330, Boston, MA 02111-1307 USA
 *
 * @package peoples
 */
/**
 * Displays a list of Users in a User Group, as well as user group name/info
 *
 * @package peoples
 */
$peoples = $modx->getService('peoples','Peoples',$modx->getOption('peoples.core_path',null,$modx->getOption('core_path').'components/peoples/').'model/peoples/',$scriptProperties);
if (!($peoples instanceof Peoples)) return '';
$modx->lexicon->load('peoples:default');

/* get usergroup */
$usergroup = $modx->getOption('usergroup',$scriptProperties,false);
if (empty($usergroup)) return '';
$c = intval($usergroup);
$c = is_int($c) && !empty($c) ? $usergroup : array('name' => $usergroup);
$usergroup = $modx->getObject('modUserGroup',$c);
if (empty($usergroup)) return $modx->lexicon('peoples.usergroup_err_nf');

/* get default properties */
$userTpl = $modx->getOption('userTpl',$scriptProperties,'pplGroupUser');
$sortBy = $modx->getOption('sortBy',$scriptProperties,'username');
$sortByAlias = $modx->getOption('sortByAlias',$scriptProperties,'modUser');
$sortDir = $modx->getOption('sortDir',$scriptProperties,'ASC');
$cls = $modx->getOption('cls',$scriptProperties,'ppl-user');
$altCls = $modx->getOption('altCls',$scriptProperties,'');
$firstCls = $modx->getOption('firstCls',$scriptProperties,'');
$lastCls = $modx->getOption('lastCls',$scriptProperties,'');
$placeholderPrefix = $modx->getOption('placeholderPrefix',$scriptProperties,'peoplegroup.');
$userClass = $modx->getOption('userClass',$scriptProperties,'modUser');
$getProfile = $modx->getOption('getProfile',$scriptProperties,false);
$profileAlias = $modx->getOption('profileAlias',$scriptProperties,'Profile');
$limit = (int)$modx->getOption('limit',$_REQUEST,$modx->getOption('limit',$scriptProperties,0));
$start = (int)$modx->getOption('start',$modx->getOption('offset',$_REQUEST,$modx->getOption('offset',$scriptProperties,0)));

/* define initial placeholders from usergroup data */
$phs = $usergroup->toArray();

/* get users */
$c = $modx->newQuery($userClass);
$c->innerJoin('modUserGroupMember','UserGroupMembers');
$c->innerJoin('modUserGroupRole','UserGroupRole','UserGroupMembers.role = UserGroupRole.id');
$c->where(array(
    'UserGroupMembers.user_group' => $usergroup->get('id'),
));
$total = $modx->getCount($userClass,$c);
$c->select($modx->getSelectColumns($userClass,$userClass));
$c->select(array(
    'UserGroupRole.name AS role',
    'UserGroupRole.id AS role_id',
));
$c->sortby($modx->escape($sortByAlias).'.'.$modx->escape($sortBy),$sortDir);
if (!empty($limit)) {
    $c->limit($limit,$start);
}
$users = $modx->getCollection($userClass,$c);
$phs['userCount'] = $total;
/* iterate */
$list = array();
$idx = 0;
$alt = false;
$iterativeCount = count($users);
$output = '';
foreach ($users as $user) {
    $userArray = $user->toArray();
    unset($userArray['cachepwd'],$userArray['password']);
    if ($getProfile) {
        $profile = $user->getOne($profileAlias);
        if ($profile) $userArray = array_merge($profile->toArray(),$userArray);
    }

    $userArray['cls'] = array();
    $userArray['cls'][] = $cls;
    if ($alt && !empty($altCls)) $userArray['cls'][] = $altCls;
    if (!empty($firstCls) && $idx == 0) {
        $userArray['cls'][] = $firstCls;
    }
    if (!empty($lastCls) && $idx == $iterativeCount-1) {
        $userArray['cls'][] = $lastCls;
    }
    $userArray['cls'] = implode(' ',$userArray['cls']);

    $list[] = $peoples->getChunk($userTpl,$userArray);
    $peoples->clearPlaceholders($userArray,'extended');
    $peoples->clearPlaceholders($userArray,'remote_data');
    $alt = !$alt;
    $idx++;
}
$outputSeparator = $modx->getOption('outputSeparator',$scriptProperties,"\n");
if (count($list) > 0) {
    /* pagination handling in conjunction with getPage */
    if (!empty($limit)) {
        $pageVarKey = $modx->getOption('pageVarKey',$scriptProperties,'page');
        $page = (int)$modx->getOption($pageVarKey,$scriptProperties,$modx->getOption($pageVarKey,$_REQUEST,1));
        $offset = (int)$modx->getOption('offset',$scriptProperties,0);
        /* cut the list of file into blocks */
        $list = array_chunk($list,$limit,true);
        /* output the current listing block */
        $output = implode($outputSeparator,$list[$page - 1]);
        /* need to make the total available without placeholder prefix for getPage */

        $modx->setPlaceholder('total',$total);
    } else {
        /* no pagination so display all results */
        $output = implode($outputSeparator,$list);
    }
} else {
  /* no people so display nothing */
  $output = '';
}

/* set placeholders and output */
$modx->setPlaceholders($phs,$placeholderPrefix);
$toPlaceholder = $modx->getOption('toPlaceholder',$scriptProperties,false);
if (!empty($toPlaceholder)) {
    $modx->setPlaceholder($toPlaceholder,$output);
    return '';
}
return $output;