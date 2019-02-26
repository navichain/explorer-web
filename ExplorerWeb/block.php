<!DOCTYPE html>
<html lang="en">
<head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="description" content="Lightweight, open-source explorer site for Bitcoin-like blockchain networks.">
	<meta name="keywords" content="explorer, lightweight, opensource, blockchain, platform">
	<meta name="author" content="BuildNewCoin">

	<!-- Favicon -->
	<link rel="icon" type="image/png" href="">

	<!-- Bootstrap & Plugins CSS -->
	<link href="./assets/bootstrap.min.css" rel="stylesheet" type="text/css">
	<link href="./assets/font-awesome.min.css" rel="stylesheet" type="text/css">

	<!-- Custom CSS -->
	<?php
	include "_common.php";
	?>

</head>
<body>

	<?php
	include "_header.php";
	include "_search.php";
	?>

	<section class="block-explorer-section section bg-bottom">
		<div class="container">
			<div class="row">
				<div class="col-lg-12">
					<div class="center-heading">
						<h2 class="section-title">Block detail</h2>
					</div>
				</div>
				<div class="offset-lg-3 col-lg-6">
					<div class="center-text">
						<p>Following information is provided by our API service. You can/should cross-check with other explorer if needed.</p>
					</div>
				</div>
			</div>			
			<div class="row m-bottom-70">
				<div class="col-lg-9 col-md-9 col-sm-12">
					<div class="table-responsive">
						<table class="table table-striped table-latests table-detail">
							<tbody>
								<tr>
									<td><strong>Block No.</strong></td>
									<td id="height-value"></td>
								</tr>
								<tr>
									<td><strong>Hash</strong></td>
									<td id="hash-value"></td>
								</tr>
								<tr>
									<td><strong>No. Transactions</strong></td>
									<td id="transaction-value"></td>
								</tr>
								<tr>
									<td><strong>Size</strong></td>
									<td id="size-value"></td>
								</tr>
								<tr>
									<td><strong>Time</strong></td>
									<td id="time-value"></td>
								</tr>
								<tr>
									<td><strong>Confirmations</strong></td>
									<td id="confirm-value"></td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
				<div class="col-lg-3 col-md-3 col-sm-12">
					<div class="qr" id="qrcode">
					</div>
				</div>
			</div>

			<div class="row">
				<div class="col-lg-12">
					<div class="center-heading">
						<h2 class="section-title">Transactions</h2>
					</div>
				</div>
				<div class="offset-lg-3 col-lg-6">
					<div class="center-text">
						<p>These transaction(s) are included inside the requesting block. Make sure that block has to pass the minimum confirmation number to be confident.</p>
					</div>
				</div>
			</div>			
			<div class="row">
				<div class="col-lg-12">
					<div class="table-responsive">
						<table class="table table-striped table-latests">
							<thead>
								<tr>
									<th>Hash</th>
								</tr>
							</thead>
							<tbody id="transaction-list">
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</section>


	<?php
	include "_footer.php";
	?>

	<script
		src="http://code.jquery.com/jquery-3.3.1.min.js"
		integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="
		crossorigin="anonymous"></script>
	<script type="text/javascript" src="qrcode.min.js"></script>
	<script type="text/javascript">
		var base_url = "http://207.148.77.154:3303";

		function formatDate(date) {
			var d = new Date(date * 1000);
			return [d.getFullYear(), d.getMonth() + 1, d.getDate()].join('-') + " " + [d.getHours(), d.getMinutes(), d.getSeconds()].join(':');
		}

		function getParameter(param) {
			var url_string = window.location.href;
			var url = new URL(url_string);
			var c = url.searchParams.get(param);
			return c;
		}

		function noResult() {
			alert('No result!');
		}

		function searchEnter(ev) {
			if (ev.keyCode == 13) {
				search()
			}
		}

		function search() {
			var term = $('#search-term').val().trim();
			if (term.length > 0) {
				// check for block hash
				$.ajax({
					url: base_url + "/search/" + term,
					type: "get",
					dataType: "json",
					success: function(resp) {
						if (resp.result != undefined) {
							if (resp.result == 'block')
								window.location.href = 'block.php?hash=' + term;
							else if (resp.result == 'transaction')
								window.location.href = 'transaction.php?hash=' + term;
							else if (resp.result == 'address')
								window.location.href = 'address.php?hash=' + term;
							else
								noResult();
						} else {
						}
					},
					error: function(resp) {
						noResult();
					}
				});
			}
		}

		$(document).ready(function(){
			var currentBlock = -1;
			var latestBlock = -1;

			// get latest blocks
			$.ajax({
				url: base_url + "/latest-blocks/1",
				type: "get",
				dataType: "json",
				success: function(resp) {
					var latestBlockHeight = resp[0].height;
					$('#latest-block-number').html(latestBlockHeight);

					if (currentBlock >= 0) {
						$('#confirm-value').html(latestBlockHeight - currentBlock + 1);
					} else {
						latestBlock = latestBlockHeight;
					}
				}
			});

			$.ajax({
				url: base_url + "/get-block/" + getParameter('hash'),
				type: "get",
				dataType: "json",
				success: function(resp) {
					blockInfo = resp;

					if (blockInfo.hash != undefined) {
						var time = formatDate(blockInfo.time);
						var block = blockInfo.height;
						var hash = blockInfo.hash;
						var transaction = blockInfo.tx.length;
						var size = blockInfo.size;

						$('#height-value').html(block);
						$('#hash-value').html(hash);
						$('#transaction-value').html(transaction);
						$('#size-value').html(size);
						$('#time-value').html(time);
						new QRCode(document.getElementById("qrcode"), hash);

						// list transaction
						$.each(blockInfo.tx, function (index, transaction){
							var link = 'onclick="window.location.href=\'transaction.php?hash='+ transaction +'\'"';
							$('#transaction-list').append('<tr '+ link +' class="cursor-pointer"><td>'+ transaction +'</td></tr>');
						});

						if (latestBlock >= 0) {
							$('#confirm-value').html(latestBlock - block + 1);
						} else {
							currentBlock = block;
						}
					} else {
						alert('We have no result!');
					}
				}
			});
		});

	</script>
</body></html>