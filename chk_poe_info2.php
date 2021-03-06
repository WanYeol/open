<?php
include_once('./_common.php');

/*
변경되나?

ajax를 이용하여 사용자가 버튼등을 클릭하여 이벤트가 발생 할 경우
관련정보를 연산하여 다시 사용자 화면에 뿌려줍니다.

여기는 사용자가 입력한 ID, 캐릭터명 등이 사기꾼인지 아닌지 판단합니다.
*/

/*
ajax를 요청할때 토큰을 생성하여

하나는 ajax할때 같이 보내고
하나는 세션에 저장합니다.
생성한 토큰과 저장된 토큰이 맞는지 확인
틀리면 즉시 종료합니다.
*/
$token = strip_tags(trim($_POST['token']));
$cjax_token = get_session('ss_cjax_poe_info_token');
set_session('ss_cjax_poe_info_token', '');
if (!($token && $cjax_token == $token)) {
	exit;
}

// acc 계정명
// char 캐릭터명
$mb_acc = strip_tags(trim($_POST['mb_acc']));
$mb_char = strip_tags(trim($_POST['mb_char']));

if($member[mb_id] != null && $member[mb_id] != ""){
	$mb_acc = $member[mb_acc];
}

if($mb_acc == null || $mb_acc == "") {
	$print['code'] = '1';
	$print['msg'] = $lang['register_not_find_poe_acc'];
	echo json_encode($print);
	exit;
}
if($mb_char == null || $mb_char == "") {
	$print['code'] = '1';
	$print['msg'] = $lang['register_not_find_poe_acc'];
	echo json_encode($print);
	exit;
}

/*
게임사 api로 계정명을 넣으면 해당 계정에 있는 캐릭터명을 반환합니다.

그리고 반환된 캐릭터명을 전부 db에 저장 및 사기관련 이력이 있는지 조회합니다.
*/
$incode_data = curl("https://www.pathofexile.com/character-window/get-characters?accountName=$mb_acc");

$data_arr = json_decode($incode_data, true);
$name_find = false;
$is_block = false;
if($data_arr['error'] == null){
	for($i=0; $i<count($data_arr); $i++){
		$db_char = $data_arr[$i]['name'];
		if($db_char == $mb_char){
			$name_find = true;
			if($data_arr[$i]['league'] == 'Standard'){
				$mb_league = "Standard";
			} else if(strpos($data_arr[$i]['league'], 'Hardcore') !== false){
				if($data_arr[$i]['league'] == "Hardcore"){
					$mb_league = "Hardcore";
				} else {
					$mb_league = $data_arr[$i]['league'];
				}
			} else {
				$mb_league = $data_arr[$i]['league'];
			}


		}

		$sql = "select * from `v5_member_acc` where `mb_acc` = '$mb_acc' and `mb_char` = '$db_char'";
		$doblue = sql_fetch($sql);
		if(isChkNull($doblue['idx'])){
			$sql = "insert into `v5_member_acc` set
				`mb_acc` = '$mb_acc',
				`mb_char` = '$db_char'";
			sql_query($sql);
		}

		$sql = "select * from `v5_block` where `mb_acc` = '$mb_acc'";
		$block_member = sql_fetch($sql);
		if(isChkNull($block_member['idx']) == false){
			if($block_member['mb_char'] != $db_char){
				$is_block = true;
			}
		}
		if($is_block == false ){
			$sql = "select * from `v5_block` where `mb_char` = '$db_char'";
			$block_member = sql_fetch($sql);
			if(isChkNull($block_member['idx']) == false){
				$is_block = true;
			}
		}
	}

}


if($is_block == true){
	$sql = "select * from `v5_member_acc` where `mb_acc` = '$mb_acc'";
	$member_acc_re = sql_query($sql);
	while($row = sql_fetch_array($member_acc_re)){
		$sql = "select * from `v5_block` where `mb_acc` = '$row[mb_acc]' and `mb_char` = '$row[mb_char]'";
		$block_member = sql_fetch($sql);
		if(isChkNull($block_member['idx'])){
			$sql = "insert into `v5_block` set
				`mb_acc` = '$row[mb_acc]',
				`mb_char` = '$row[mb_char]'";
			sql_query($sql);
		}
	}

	$print['code'] = '1';
	$print['msg'] = $lang['blocked_accounts'];
	echo json_encode($print);
	exit;
}



if($name_find == false){
	$print['code'] = '1';
	$print['msg'] = $lang['register_not_find_poe_acc'];
	echo json_encode($print);
	exit;
}

if($member[mb_id] != null && $member[mb_id] != ""){
	$sql = "update `g5_member` set
		`mb_char` = '$mb_char',
		`mb_league` = '$mb_league'
		where `mb_id` = '$member[mb_id]'";
	sql_query($sql);
} else {
	$sql = "select * from `g5_member` where `mb_acc` = '$mb_acc'";
	$overlap_member = sql_fetch($sql);


	if($mb_acc != "zgogoflvhxjz") {
		if(isChkNull($overlap_member['mb_id']) == false){
			$print['code'] = '1';
			$print['msg'] = $lang['already_signed_up'];
			echo json_encode($print);
			exit;
		}
	}

}

$print['code'] = '0';
$print['mb_char'] = $mb_char;
$print['mb_league'] = $mb_league;
$print['msg'] = $lang['register_has_been_confirmed'];

echo json_encode($print);