<?php

/**
 * 妖怪与和尚过河问题
 * 描述: 有三个和尚和三个妖怪要利用唯一一条小船过河, 这条小船一次只能载两个人, 同时, 无论是在河的两岸还是在船上, 只要妖怪的的数量大于和尚的数量, 妖怪们就会将和尚吃掉. 现在需要选择一种过河的安排, 保证和尚和妖怪能过河且和尚不能被妖怪吃掉. 
 * 
 * 1. 类似三个水桶等分8升水的问题, 可以使用穷举法
 * 2. 解决本问题的算法的关键是建立状态和动作的数学模型, 并找到一种持续驱动动作产生的搜索方法. 
 * 
 */

$start = microtime(true);
 /**
  * 建立数据模型
  */

// 小船位置常量
define('LOCAL', 0);
define('REMOTE', 1);

// 过河动作名称
define('ONE_MONSTER_GO', 0);
define('TWO_MONSTER_GO', 1);
define('ONE_MONK_GO', 2);
define('TWO_MONK_GO', 3);
define('ONE_MONSTER_ONE_MONK_GO', 4);
define('ONE_MONSTER_BACK', 5);
define('TWO_MONSTER_BACK', 6);
define('ONE_MONK_BACK', 7);
define('TWO_MONK_BACK', 8);
define('ONE_MONSTER_ONE_MONK_BACK', 9);
define('INVALID_ACTION_NAME', 10);

/**
 * 状态数学模型
 */
class ItemState
{
    public $localMonster;
    public $localMonk;
    public $remoteMonster;
    public $remoteMonk;
    public $boat;

    public $curAct;

    public function __construct($localMonster = 0, $localMonk = 0, $remoteMonster = 0, $remoteMonk = 0, $boat = LOCAL)
    {
        $this->localMonster = $localMonster;
        $this->localMonk = $localMonk;
        $this->remoteMonster = $remoteMonster;
        $this->remoteMonk = $remoteMonk;
        $this->boat = $boat;
    }

    /**
     * 验证是否能进行这个动作
     */
    public function canTakeAction($actEffect)
    {
        if($this->boat == $actEffect['boat_to']) 
            return false;
        
        if((($this->localMonster + $actEffect['move_monster']) < 0)
            || (($this->localMonster + $actEffect['move_monster']) > 3)
        )
            return false;
        
        if(
            (($this->localMonk + $actEffect['move_monk']) < 0)
            || (($this->localMonk + $actEffect['move_monk']) > 3)
        )
            return false;

        return true;
    }

    /**
     * 验证是否是最终状态
     */
    public function isFinalState()
    {
        return $this->localMonster == 0 
        && $this->localMonk == 0
        && $this->remoteMonster == 3
        && $this->remoteMonk == 3
        && $this->boat == REMOTE;
    }

    /**
     * 判断是否是有效的状态
     * 1. 妖怪和和尚的总量
     * 2. 在同一岸边的妖怪数量不能大于和尚数量
     */
    public function isValiadState()
    {
        if(($this->localMonster > $this->localMonk) && ($this->localMonk != 0)){
            return false;
        }
            
        
        if(($this->remoteMonster > $this->remoteMonk) && ($this->remoteMonk != 0)){
            return false;
        }
            

        if(($this->localMonster + $this->remoteMonster) != 3
            || ($this->localMonk + $this->remoteMonk) != 3
        ){
            return false;
        }
            
        return true;
    }
}

/**
 * 过河动作数学模型
 */

$actEffects = [
    [
        'act' => ONE_MONSTER_GO,
        'boat_to' => REMOTE,
        'move_monster' => -1,
        'move_monk' => 0
    ],
    [
        'act' => TWO_MONSTER_GO,
        'boat_to' => REMOTE,
        'move_monster' => -2,
        'move_monk' => 0
    ],
    [
        'act' => ONE_MONK_GO,
        'boat_to' => REMOTE,
        'move_monster' => 0,
        'move_monk' => -1
    ],
    [
        'act' => TWO_MONK_GO,
        'boat_to' => REMOTE,
        'move_monster' => 0,
        'move_monk' => -2
    ],
    [
        'act' => ONE_MONSTER_ONE_MONK_GO,
        'boat_to' => REMOTE,
        'move_monster' => -1,
        'move_monk' => -1
    ],
    [
        'act' => ONE_MONSTER_BACK,
        'boat_to' => LOCAL,
        'move_monster' => 1,
        'move_monk' => 0
    ],
    [
        'act' => TWO_MONSTER_BACK,
        'boat_to' => LOCAL,
        'move_monster' => 2,
        'move_monk' => 0
    ],
    [
        'act' => ONE_MONK_BACK,
        'boat_to' => LOCAL,
        'move_monster' => 0,
        'move_monk' => 1
    ],
    [
        'act' => TWO_MONK_BACK,
        'boat_to' => LOCAL,
        'move_monster' => 0,
        'move_monk' => 2
    ],
    [
        'act' => ONE_MONSTER_ONE_MONK_BACK,
        'boat_to' => LOCAL,
        'move_monster' => 1,
        'move_monk' => 1
    ],
];


/**
 * 搜索算法
 */
function searchState(&$states)
{
    global $actEffects;
    $current = $states[count($states) - 1];

    if($current->isFinalState()){
        printResult($states);
        return ;
    }

    foreach($actEffects as $actEffect){
        searchStateOnNewAction($states, $current, $actEffect);
    }
}

function searchStateOnNewAction(&$states, &$current, &$actEffect)
{
    $next = new ItemState();
    if(makeActionNewState($current, $actEffect, $next)){
        
        if($next->isValiadState() && !isProcessedState($states, $next)){
            array_push($states, $next);
            searchState($states);
            array_pop($states);
        }
    }
}

/**
 * 剪枝和重复状态判断
 */
function isProcessedState(&$states, &$next)
{
    foreach($states as $state){
        if(
            $state->localMonster == $next->localMonster
            && $state->localMonk == $next->localMonk
            && $state->remoteMonster == $next->remoteMonster
            && $state->remoteMonk == $next->remoteMonk
            && $state->boat == $next->boat
        ){
            return true;
        }
    }
    return false;
}

/**
 * 通过过河动作属性列表对所有动作进行一致性处理的体现
 */
function makeActionNewState(&$current, &$actEffect, &$newState)
{
    if($current->canTakeAction($actEffect)){
        $newState = clone $current;

        $newState->localMonster += $actEffect['move_monster'];
        $newState->localMonk += $actEffect['move_monk'];
        $newState->remoteMonster -= $actEffect['move_monster'];
        $newState->remoteMonk -= $actEffect['move_monk'];
        $newState->boat = $actEffect['boat_to'];
        $newState->curAct = $actEffect['act'];

        return true;
    }

    return false;
}

/**
 * 输出结果
 */
function printResult(&$states)
{
    static $i = 1;
    $string = '';

    foreach($states as $state){
        $string .= "->[{$state->localMonster},{$state->localMonk},{$state->remoteMonster},{$state->remoteMonk},{$state->boat}]";
    }

    echo $i . '. ' . $string, "\n";
    $i++;
}

/**
 * 运算
 */
$states = [];   // 记录已经处理过的转态

$first = new ItemState(3, 3, 0, 0, LOCAL);
$states[] = $first;

searchState($states);

$use = bcsub(microtime(true), $start, 5);
echo "use {$use}s\n";