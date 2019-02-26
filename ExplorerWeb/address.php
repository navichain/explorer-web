<!DOCTYPE html>
<html lang="en">
<head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="description" content="ChainUs is a blockchain platform for future.">
	<meta name="keywords" content="chainus, chus, blockchain, platform">
	<meta name="author" content="HoangDN">

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
						<h2 class="section-title">Address detail</h2>
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
									<td><strong>Address</strong></td>
									<td id='address-value'></td>
								</tr>
								<tr>
									<td><strong>Balance</strong></td>
									<td id='current-balance'>0</td>
								</tr>
								<tr>
									<td><strong>Number of transactions</strong></td>
									<td id='total-trans'></td>
								</tr>
								<tr>
									<td><strong>Input balance</strong></td>
									<td id='total-income'></td>
								</tr>
								<tr>
									<td><strong>Output balance</strong></td>
									<td id='total-outcome'></td>
								</tr>
								<tr>
									<td><strong>Unconfirmed balance</strong></td>
									<td id='unconfirmed-balance'></td>
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
						<p>These transaction(s) are input and output transactions of requesting address</p>
					</div>
				</div>
			</div>

			<div id="income-trans-div">
				<h4>Input transactions</h4>
				<div class="row">
					<div class="col-lg-12">
						<div class="table-responsive">
							<table class="table table-striped table-latests">
								<thead>
									<tr>
										<th>Hash</th>
										<th>Block</th>
										<th>Balance</th>
										<th>Confirmations</th>
									</tr>
								</thead>
								<tbody id="transactions-in">
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>

			<div id="outcome-trans-div">
				<h4>Output transactions</h4>
				<div class="row">
					<div class="col-lg-12">
						<div class="table-responsive">
							<table class="table table-striped table-latests">
								<thead>
									<tr>
										<th>Hash</th>
										<th>Block</th>
										<th>Balance</th>
										<th>Confirmations</th>
									</tr>
								</thead>
								<tbody id="transactions-out">
								</tbody>
							</table>
						</div>
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
			return [d.getFullYear(), d.getMonth() + 1, d.getDate()].join('-') + " " + [d.getHours(), d.getMinutes()].join(':');
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

			// get latest blocks
			$.ajax({
				url: base_url + "/latest-blocks/1",
				type: "get",
				dataType: "json",
				success: function(resp) {
					var latestBlockHeight = resp[0].height;
					$('#latest-block-number').html(latestBlockHeight);
				}
			});

			$.ajax({
				url: base_url + "/get-balance/" + getParameter('hash'),
				type: "get",
				dataType: "json",
				success: function(resp) {
					$('#address-value').html(getParameter('hash'));
					$('#current-balance').html(resp[2]);
					$('#total-income').html(resp[0]);
					$('#total-outcome').html(resp[1]);
					$('#total-trans').html(resp[3].length + resp[4].length);
					new QRCode(document.getElementById("qrcode"), getParameter('hash'));

					resp[3].reverse();
					resp[4].reverse();

					if (resp[3].length == 0)
						$('#income-trans-div').hide();
					if (resp[4].length == 0)
						$('#outcome-trans-div').hide();

					var total_in_all = 0;
					var total_out_all = 0;

					$.each(resp[3], function(index, trans) {
						var item = '';
						item += '<tr>';
						item += '	<td><a href="transaction.php?hash='+ trans[0] +'">'+ trans[0] +'</a></td>'; // hash
						item += '	<td>'+ trans[2] +'</td>'; // block height
						item += '	<td style="color: green">+'+ trans[1] +'</td>'; // balance
						item += '	<td>'+ trans[3] +'</td>'; // confirmations
						item += '</tr>';

						$('#transactions-in').append(item);
						total_in_all += trans[1];
					});

					$.each(resp[4], function(index, trans) {
						var item = '';
						item += '<tr>';
						item += '	<td><a href="transaction.php?hash='+ trans[0] +'">'+ trans[0] +'</a></td>'; // hash
						item += '	<td>'+ trans[2] +'</td>'; // block height
						item += '	<td style="color: red">-'+ trans[1] +'</td>'; // balance
						item += '	<td>'+ trans[3] +'</td>'; // confirmations
						item += '</tr>';

						$('#transactions-out').append(item);
						total_out_all += trans[1];
					});

					$('#unconfirmed-balance').html(Number(total_in_all - total_out_all - resp[2]).toFixed(8) * 1);
				}
			});
		});
	</script>
</body></html>