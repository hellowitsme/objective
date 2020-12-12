<?php

ini_set('log_errors', 'Off'); //ログを取る
ini_set('error_log', 'php.log'); //ログ出力ファイル
session_start();

//人格納用
$humans = array();
//神格納用
$gods = array();

//性別クラス
class Sex{
  const MAN = 1;
  const WOMEN = 2;
}
// 抽象クラス
abstract class Creature{
  //プロパティ
  protected $name;
  protected $hp;
  protected $attackMin;
  protected $attackMax;
  //抽象メソッド
  abstract public function setCry();
  //セッター
  public function setName($str){
    $this->name = $str;
  }
  public function setHp($num){
    $this->hp = $num;
  }
  //ゲッター
  public function getName(){
    return $this->name;
  }
  public function getHp(){
    return $this->hp;
  }
  //メソッド
  public function attack($target){
    $attackPoint = mt_rand($this->attackMin, $this->attackMax);
    if(!mt_rand(0,9)){ //10/1で快心の一撃
      $attackPoint = $attackPoint *= 1.5;
      $attackPoint = (int)$attackPoint;
      History::set($this->getName().'の快心の一撃！');
    }
    $target->setHp($target->getHp() - $attackPoint);
    History::set($attackPoint.'ポイントのダメージ！');
  }
}
//人クラス
class Human extends Creature{
  private $sex;
  public function __construct($name, $sex, $hp, $attackMin, $attackMax){
    $this->name = $name;
    $this->sex = $sex;
    $this->hp = $hp;
    $this->attackMin = $attackMin;
    $this->attackMax = $attackMax;
  }
  //セッター
  public function setSex($num){
    $this->sex = $num;
  }
  //ゲッター
  public function getSex(){
    return $this->sex;
  }
  //メソッド
  public function setCry(){
    switch($this->sex){
      case Sex::MAN:
        History::set('ぐはっっっ！');
      break;
      case Sex::WOMEN:
        History::set('きゃっっ！');
      break;
    }
  }
}

//神クラス
class God extends Creature{
  //プロパティ
  protected $img;
  //コンストラクタ
  public function __construct($name, $hp, $img, $attackMin, $attackMax){
    $this->name = $name;
    $this->hp = $hp;
    $this->img = $img;
    $this->attackMin = $attackMin;
    $this->attackMax = $attackMax;
  }
  public function setCry(){
    History::set('きかぬわー！！！');
  }
  //ゲッター
  public function getImg(){
    return $this->img;
  }
}
//ゼウスクラス
class Zeus extends God{
  //プロパティ
  private $zeusAttack;
  function __construct($name, $hp, $img, $attackMin, $attackMax, $zeusAttack){
    parent::__construct($name, $hp, $img, $attackMin, $attackMax);
    $this->zeusAttack = $zeusAttack;
  }
  //ゲッター
  public function getZeusAttack(){
    return $this->zeusAttack;
  }
  //オーバーライド
  public function attack($target){
    $attackPoint = $this->attack;
    if(!mt_rand(0,2)){// 3/1でzeusAttack
      History::set($this->name.'怒りの鉄槌！');
      $target->setHp($target->getHp() - $this->zeusAttack);
      History::set($this->zeusAttack.'ポイントのダメージ！');
    }else{
      parent::attack($target);
    }
  }
}
//インターフェース
interface HistoryInterface{
  public static function set($str);
  public static function clear();
}
//履歴管理クラス
class History implements HistoryInterface{
  public static function set($str){
    if(empty($_SESSION['history'])) $_SESSION['history'] = '';
    $_SESSION['history'] .= $str.'<br>';
  }
  public static function clear(){
    unset($_SESSION['history']);
  }
}
//インスタンス生成
$humans[] = new Human('勇者', Sex::MAN, 1000, 40, 120);
$humans[] = new Human('女勇者', Sex::WOMEN, 800, 20, 200);
$gods[] = new God('アヌビス', 300, 'img/god1.png', 10, 30);
$gods[] = new God('オーディン', 400, 'img/god2.png', 15, 35);
$gods[] = new God('ハーデス', 500, 'img/god3.png', 20, 40);
$gods[] = new God('アポロン', 600, 'img/god4.png', 25, 45);
$gods[] = new Zeus('ゼウス', 1000, 'img/god5.png', 50, 100, mt_rand(200, 300));
$gods[] = new God('おかめさん', 10000, 'img/god6.png', 150, 200);


//関数
function createGod(){
  global $gods;
  $god = $gods[mt_rand(0, 5)];
  History::set($god->getName().'降臨！');
  $_SESSION['god'] = $god;
}
function createHuman(){
  global $humans;
  $human = $humans[mt_rand(0,1)];
  $_SESSION['human'] = $human;
}
function init(){
  History::clear();
  History::set('初期化します。');
  $_SESSION['knockDownCount'] = 0;
  createHuman();
  createGod();
}
function gameOver(){
  $_SESSION = array();
}


//POST時の処理
if(!empty($_POST)){
  $attackFlg = (!empty($_POST['attack'])) ? true : false;
  $startFlg = (!empty($_POST['start'])) ? true : false;
  error_log('POSTされています');

  if($startFlg){
    History::set('ゲームスタート！');
    init();
  }else{
    //攻撃するを押した場合
    if($attackFlg){
      //神に攻撃
      History::set($_SESSION['human']->getName().'の攻撃！！！！');
      $_SESSION['human']->attack($_SESSION['god']);
      $_SESSION['god']->setCry();
      //神の反撃
      History::set($_SESSION['god']->getName().'の攻撃！！！！');
      $_SESSION['god']->attack($_SESSION['human']);
      $_SESSION['human']->setCry();

      //人のHPが0になったら終了
      if($_SESSION['human']->getHp() <= 0){
        gameOver();
      }else{
        if($_SESSION['god']->getHp() <= 0){
          History::set($_SESSION['god']->getName().'を倒した！！');
          createGod();
          $_SESSION['knockDownCount'] = $_SESSION['knockDownCount'] +1;
        }
      }
    }else{
      History::set('戦略的撤退！');
      createGod();
    }
  }
  $_POST = array();
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <title>Objective</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link type="text/css" rel="stylesheet" href="./css/reset.css">
  <link type="text/css" rel="stylesheet" href="./css/style.css">
</head>
<body>
  

  <h1 class="game-title">「Defeat Gods!!」</h1>
    <main class="main-container">
      <div class="main-contents">
        <?php if(empty($_SESSION)){ ?>
        <section class="start-container">
            <h2 class="start-txt">GAME START ?</h2>
            <form method="post">
              <div class="start-btn">
                <input type="submit" name="start" value="▶ゲームスタート">
              </div>
            </form>
        </section>
          <?php }else{ ?>
        <section class="god-container">
          <h2><?php echo $_SESSION['god']->getName().'降臨'; ?></h2>
          <div class="img-container">
            <img src="<?php echo $_SESSION['god']->getImg(); ?>" class="img">
          </div>
          <p class="hp"><?php echo $_SESSION['god']->getName(); ?>のHP：<?php echo $_SESSION['god']->getHp(); ?></p>
        </section>
        <section class="human-container">
          <p class="knockdown">倒した神の数：<?php echo $_SESSION['knockDownCount']; ?></p>
          <p class="human-name"><?php echo $_SESSION['human']->getName(); ?>の残りHP：<?php echo $_SESSION['human']->getHp(); ?></p>
          <div class="human-form-container">
            <form action="" method="post" class="attack">
              <input type="submit" name="attack" value="▶攻撃する">
            </form>
            <form action="" method="post" class="run">
              <input type="submit" name="escape" value="▶逃げる">
            </form>
            <form action="" method="post" class="restart">
              <input type="submit" name="start" value="▶ゲームリスタート">
            </form>
          </div>
          <div class="history js-auto-scroll">
            <p><?php echo (!empty($_SESSION['history'])) ? $_SESSION['history'] : ''; ?></p>
          </div>
        </section>
      <?php } ?>
      </div>
    </main>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js" integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>

<script>
  $(function(){
  $('.js-auto-scroll').delay(100).animate({
    scrollTop: $(document).height()
  }, 500);
  });
</script>
</body>
</html>