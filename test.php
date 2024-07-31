<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require('classes/Esirion/OpenEducationBadges/OpenEducationBadgesApi.php');
use Esirion\OpenEducationBadges\OpenEducationBadgesApi;


// $api = new OpenEducationBadgesApi(
// 	client_id: 'Q4hS7HwNTnkdZKjZB6N1aDAZODraPKBpsFIayyWY',
// 	client_secret: 'ZKJrT5kdFWje4LFJYDbzl0JLddvg70HmsOW9x3XzajRgTcleJYlJfxJPUVo2eXfFDTrGvtyFVYfZsUTSk7sOUWOHNgwnq3TliPW4uflgOebx3xXg11huElqCEbaKcpQA',
// 	retrieve_token: function () {
// 		return false;
// 	}
// );
// var_dump($api->get_access_token());


$api = new OpenEducationBadgesApi(
	client_id: 'ttxeiccoVp05GukHybCCpRB05JA1QmnB5ludyLDM',
	client_secret: 'tJwZodXDvcOGrQQSvKtPjfuq1cOBrthJy2UdG2H2ehkrHMxYvZTN75jnnRwqqA8ayKeI61NqWq4fG4AF5BVyVKyhHNz67WeIT7DUZqm4Q4XjLBZoQNIj1Sg8DKq7hCjI',
	retrieve_token: function () {
		return false;
	}
);

$all_badges = $api->get_all_badges();
if ($all_badges) {
	echo "<h2>All badges</h2>";
	foreach ($all_badges as $badge) {
		echo '<img src="'.$badge['image'].'" width="96">';
	}
	// if ($badges[0]) {
	// 	$badge_class = $badges[0]['slug'];
	// 	$issuer_id = substr($badges[0]['issuer'], strrpos($badges[0]['issuer'], '/') + 1);
	// 	$api->issue_badge($issuer_id, $badge_class, 'tiago.joao@esirion.de');
	// }
}

$issuers = $api->get_issuers();
foreach ($issuers as $issuer) {
	echo "<h2>".$issuer['name']."</h2><ul>";
	$badges = $api->get_badges($issuer['slug']);
	foreach ($badges as $badge) {
		echo '<img src="'.$badge['image'].'" width="96">';
	}
	echo "</ul><ul>";
	$assertions = $api->get_assertions($issuer['slug']);
	$badges_by_mail = [];
	foreach ($assertions as $assertion) {
		// var_export($assertion);
		if (empty($badges_by_mail[$assertion['recipient_identifier']])) {
			$badges_by_mail[$assertion['recipient_identifier']] = [];
		}
		$badges_by_mail[$assertion['recipient_identifier']][] = $assertion['image'];
		// echo "<li>".$assertion['recipient_identifier']."</li>";
	}
	foreach ($badges_by_mail as $email => $images) {
		echo "<li>$email ";
		foreach ($images as $image) {
			echo '<img src="'.$image .'" width="32">';
		}
		echo "</li>";
	}
	echo "</ul>";
}
