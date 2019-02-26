var express = require('express')
var cors = require('cors')
var app = express()
var MongoClient = require('mongodb').MongoClient;
var ObjectID = require('mongodb').ObjectID

const request = require('request');

const rpcUser = "hoang";
const rpcPass = "hoang";
const rpcUrl = "http://localhost:13889";
const minConfirmation = 5;
const appPort = 3303;

var url = 'mongodb://207.148.77.154:27017/';
var globalDB = null;

var intervalBlock = null;
var intervalTransaction = null;

var nextBlockHash = '';
var transactionList = [];
var busy = false; // transaction busy flag
var blockBusy = false; // block busy flag

app.use(cors())
app.use(express.json());
app.use(express.urlencoded());

var requestOpt = {
	url: rpcUrl,
	method: "post",
	headers:
	{
		"content-type": "text/plain"
	},
	auth: {
		user: rpcUser,
		pass: rpcPass
	},
	body: JSON.stringify( {"jsonrpc": "2.0", "id": "curltest", "method": "getblockchaininfo", "params": [] })
};

// ========================================================================
// ========================================================================

MongoClient.connect(url, function(err, db) {
	var dbo = db.db("qtum");
	globalDB = dbo;

	// after has more than one block
	getMaxDBBlock();
	setInterval(function(){getMaxDBBlock()}, 60 * 1000);

	// un-comment on the first time
	// removeLatestBlocks(0);

}); 
// ========================================================================
// ========================================================================

function getBlockHash(blocknumber, fulldetail) {
	let options = requestOpt;
	options.body = JSON.stringify( {"jsonrpc": "2.0", "id": "curltest", "method": "getblockhash", "params": [blocknumber*1] });

	request(options, (error, response, body) => {
		if (error) {
			return ({"result":"error"});
		} else {
			let blockHash = JSON.parse(body).result;
			if (fulldetail)
				return getBlockInfo(blockHash);
			else
				return ({'blockhash': blockHash});
		}
	});
}

function getBlockInfo(blockhash) {
	nextBlockHash = '';
	console.log('Get block', blockhash);

	let options = requestOpt;
	options.body = JSON.stringify( {"jsonrpc": "2.0", "id": "curltest", "method": "getblock", "params": [blockhash] });

	request(options, (error, response, body) => {
		if (error) {
			return ({"result":"error"});
		} else {
			var ok = true;

			if (body != undefined && body.result) {
				body = body.result;
			} else {
				try {
					body = JSON.parse(body);
					body = body.result;
				} catch(e) {
					console.log("not JSON ", blockhash);
					ok = false;
				}
			}

			if (ok) {
				// get transaction list
				if (body.tx.length > 0) {
					for(tc = 0; tc < body.tx.length; tc++) {
						transactionList.push([body.tx[tc], body.height]);
					}
				}

				insertIntoDB('block', blockhash, body);
				nextBlockHash = body.nextblockhash;
				console.log('has next block');
			}
		}
	});
}

function getRawTransaction(transactionhash, blockheight) {
	let options = requestOpt;
	options.body = JSON.stringify( {"jsonrpc": "2.0", "id": "curltest", "method": "getrawtransaction", "params": [transactionhash] });

	request(options, (error, response, body) => {
		if (error) {
			return ({"result":"error"});
		} else {
			getTransaction(JSON.parse(body).result, blockheight);
		}
	});
}

function getTransaction(transactiondata, blockheight) {
	if (transactiondata == null) {
		busy = false;
		return null;
	}

	let options = requestOpt;
	options.body = JSON.stringify( {"jsonrpc": "2.0", "id": "curltest", "method": "decoderawtransaction", "params": [transactiondata] });

	request(options, (error, response, body) => {
		if (error) {
			return ({"result":"error"});
		} else {
			let tranInfo = JSON.parse(body).result;

			tranInfo.blockheight = blockheight;

			insertIntoDB('transaction', tranInfo.hash, tranInfo);
			busy = false;
			return tranInfo;
		}
	});
}

function compareRpcLastHash(dbHash, blocknumber) {
	let options = requestOpt;
	options.body = JSON.stringify( {"jsonrpc": "2.0", "id": "curltest", "method": "getbestblockhash", "params": [] });

	request(options, (error, response, body) => {
		if (error) {
			return ({"result":"error"});
		} else {
			let tranInfo = JSON.parse(body).result;

			if (tranInfo == dbHash) {
				console.log('No new block, do nothing');
			} else {
				// check if it's the old hash
				options.body = JSON.stringify( {"jsonrpc": "2.0", "id": "curltest", "method": "getblockhash", "params": [blocknumber] });
				request(options, (error, response, body) => {
					if (error) {
						return ({"result":"error"});
					} else {
						let tranInfo = JSON.parse(body).result;

						if (tranInfo == dbHash) {
							// not forked blockchain
							console.log('Update, not replace');
							updateLatestBlocks(blocknumber);
						} else {
							//remove 5 latest blocks and re-get them all
							console.log('Replace last N blocks');
							var cursor = globalDB.collection('block').aggregate([{ $group : { _id: '', max: { $max : "$detail.height" }}}], null);
							cursor.on("data", function (data) {
								console.log("Get from block: ", data.max - minConfirmation - 2);
								var blocknumber = Math.max(0, data.max - minConfirmation - 2);
								removeLatestBlocks(blocknumber);
							});
						}
					}
				});

			}
			return tranInfo;
		}
	});
}

async function getMaxDBBlock() {
	if (blockBusy == false && busy == false) {
		var dbHash_number = await checkForLastBlock();
		compareRpcLastHash(dbHash_number[0], dbHash_number[1]);
	}
}

function insertIntoDB(collection, hash, data) {
	globalDB.collection(collection).update(
		{hash: hash},
		{$set:
			{
				hash: hash,
				detail: data
			}
		},
		{upsert: true, safe: false},

		function(err,data){
			if (err){
			console.log(err);
			} else {
				console.log("saved");
			}
		}
	);
}

async function checkForLastBlock() {
	var maxBlock = 0;
	var lastDbHash = '';

	// get last db block height
	await globalDB.collection('block').aggregate([{ $group : { _id: '', max: { $max : "$detail.height" }}}], async function(error, respMaxBlock) {
		await respMaxBlock.forEach(function(bl) {
			maxBlock = bl.max;
		});
	});

	// get last block hash
	await globalDB.collection('block').find({'detail.height': maxBlock}, async function(error, respMaxBlock) {
		await respMaxBlock.forEach(function(bl) {
			lastDbHash = bl.hash;
		});
	});

	return [lastDbHash, maxBlock];
}

function removeLatestBlocks(blocknumber) {
	var done = 0;
	// remove block collection
	globalDB.collection('block').remove({'detail.height': {'$gte': blocknumber}}, function(err,data){
			if (err){
				console.log(err);
			} else {
				done++;
				console.log("removed old blocks");
				if (done == 2) {
					updateLatestBlocks(blocknumber);
				}
			}
		}
	);

	// remove transaction collection
	globalDB.collection('transaction').remove({'detail.blockheight': {'$gte': blocknumber}}, function(err,data){
			if (err){
				console.log(err);
			} else {
				done++;
				console.log("removed old transactions");
				if (done == 2) {
					updateLatestBlocks(blocknumber);
				}
			}
		}
	);
}

function updateLatestBlocks(blocknumber) {
	getBlockHash(blocknumber, true);

	intervalBlock = setInterval(function(){
		if (nextBlockHash != undefined && nextBlockHash.length > 5) {
			blockBusy = true;
			getBlockInfo(nextBlockHash);
		} else {
			clearInterval(intervalBlock);
			blockBusy = false;
			console.log('Stop block interval...');
		}
	}, 100);

	intervalTransaction = setInterval(function() {
		if (busy == false && transactionList.length > 0) {
			var transactionhash = transactionList[0][0];
			var blockheight = transactionList[0][1];

			transactionList.shift();
			busy = true;

			console.log('Get transaction', transactionhash);
			getRawTransaction(transactionhash, blockheight);
		} else if (transactionList.length == 0 && blockBusy == false) {
			clearInterval(intervalTransaction);
			console.log('Stop transaction interval...');
		}
	}, 100);
}

// =========================================================================================================================
async function getAddressBalance(res, address) {
	var startTime = (new Date()).getTime();
	var txids = [];
	var balanceIn = 0;
	var balanceOut = 0;
	var inputTrans = [];
	var outputTrans = [];


	await globalDB.collection('transaction').find({'detail.vout.scriptPubKey.addresses': address}, async function(error, resp){
		var maxBlock = 0;
		await globalDB.collection('block').aggregate([{ $group : { _id: '', max: { $max : "$detail.height" }}}], async function(error, respMaxBlock) {
			await respMaxBlock.forEach(function(bl) {
				maxBlock = bl.max + 1;
			});
		});

		await resp.forEach(function(tx){
			var txid = tx.detail.txid;
			var trans = tx.detail.vout;

			for(n = 0; n < trans.length; n++) {
				if (trans[n].scriptPubKey.addresses != undefined && trans[n].scriptPubKey.addresses[0] == address) {
					if(tx.detail.blockheight < maxBlock - minConfirmation) {
						// successful transaction
						balanceIn += trans[n].value;
					}

					txids.push([txid, n, trans[n].value]);
					inputTrans.push([txid, trans[n].value, tx.detail.blockheight, maxBlock - tx.detail.blockheight]);
				}
			}
		});

		for(i = 0; i < txids.length; i++) {
			await globalDB.collection('transaction').find({'detail.vin.txid': txids[i][0]}, async function(error, resp2){
				await resp2.forEach(function(tx) {
					var trans = tx.detail.vin;
					for(n = 0; n < trans.length; n++) {
						if (trans[n].txid == txids[i][0] && trans[n].vout == txids[i][1]) {
							balanceOut += txids[i][2];
							outputTrans.push([tx.detail.txid, txids[i][2], tx.detail.blockheight, maxBlock - tx.detail.blockheight]);
						}
					}
				});
			});
		}

		// return as API
		var rest = [Number(balanceIn).toFixed(8)*1, Number(balanceOut).toFixed(8)*1, Number(balanceIn - balanceOut).toFixed(8)*1, inputTrans, outputTrans, (new Date).getTime() - startTime];
		res.json(rest);
	});
}


async function getTransactionDetail(res, txid) {
	var startTime = (new Date()).getTime();
	var txids = [];
	var balanceIn = 0;
	var balanceOut = 0;
	var inputTrans = [];
	var outputTrans = [];
	var maxX = 10000;
	var responsed = false;

	await globalDB.collection('transaction').find({'detail.txid': txid}, async function(error, resp){
		await resp.forEach(async function(tx){
			responsed = true;
			var transOut = tx.detail.vout;

			for(n = 0; n < transOut.length; n++) {
				if (transOut[n].scriptPubKey.addresses != undefined) {
					balanceOut += transOut[n].value;
					outputTrans.push([transOut[n].scriptPubKey.addresses[0], transOut[n].value]);
				}
			}

			for(i = 0; i < tx.detail.vin.length; i++) {
				if (tx.detail.vin[i].txid == undefined) {
					// coinbase
					inputTrans.push(['coinbase', 0]);
				} else {
					await globalDB.collection('transaction').find({'detail.txid': tx.detail.vin[i].txid}, async function(error, resp2){
						await resp2.forEach(function(tx2) {
							var trans = tx2.detail.vout[tx.detail.vin[i].vout];
							balanceIn += trans.value;
							inputTrans.push([trans.scriptPubKey.addresses[0], trans.value]);
						});
					});
				}
			}
		
			var rest = tx.detail;
			rest.balanceIn = Number(balanceIn).toFixed(8)*1;
			rest.balanceOut = Number(balanceOut).toFixed(8)*1;
			rest.inputTransactions = inputTrans;
			rest.outputTransactions = outputTrans;

			await res.json(rest);
		});
	});

	if (responsed == false)
		res.json({result:'none'});
}

async function getLatestBlocks(res, blockCount) {
	// get last block height
	var maxBlock = 0;
	await globalDB.collection('block').aggregate([{ $group : { _id: '', max: { $max : "$detail.height" }}}], async function(error, respMaxBlock) {
		await respMaxBlock.forEach(function(bl) {
			maxBlock = bl.max;
		});
	});

	globalDB.collection('block').find({'detail.height': {$gt: maxBlock - blockCount}}, async function(error, resp2){
		var output = [];
		await resp2.forEach(function(bl) {
			var detail = bl.detail;
			detail.confirmations = maxBlock - detail.height + 1;
			output.push(detail);
		});

		res.json(output);
	});
}

function getDbBlockByHash(res, blockhash) {
	globalDB.collection('block').find({'detail.hash': blockhash}, async function(error, resp){
		await resp.forEach(function(bl) {
			var detail = bl.detail;
			res.json(detail);
			return true;
		});

		try {
			res.json({result:'none'});
		} catch(e) {}
	});
}

function search(res, term) {
	var count = 0;

	globalDB.collection('block').find({'detail.hash': term}, async function(error, resp){
		await resp.forEach(function(bl) {
			res.json({result:'block'});
			return true;
		});

		count++;
		if (count == 3) {
			try {
				res.json({result:'none'});
			} catch(e) {};
		}
	});

	globalDB.collection('transaction').find({'detail.txid': term}, async function(error, resp){
		await resp.forEach(function(bl) {
			res.json({result:'transaction'});
			return true;
		});

		count++;
		if (count == 3) {
			try {
				res.json({result:'none'});
			} catch(e) {};
		}
	});

	globalDB.collection('transaction').find({'detail.vout.scriptPubKey.addresses': term}, async function(error, resp){
		await resp.forEach(function(bl) {
			res.json({result:'address'});
			return true;
		});

		count++;
		if (count == 3) {
			try {
				res.json({result:'none'});
			} catch(e) {};
		}
	});
}

app.get('/search/:term', function (req, res) {
	let term = req.params.term;

	search(res, term);
});

app.get('/get-block/:blockHash', function (req, res) {
	let blockHash = req.params.blockHash;

	getDbBlockByHash(res, blockHash);
});

app.get('/get-balance/:address', function (req, res) {
	let address = req.params.address;

	getAddressBalance(res, address);
});

app.get('/get-transaction/:txid', function (req, res) {
	let txid = req.params.txid;

	getTransactionDetail(res, txid);
});

app.get('/latest-blocks/:count', function(req, res) {
	let blockCount = req.params.count;

	getLatestBlocks(res, blockCount);
});

app.listen(appPort,'0.0.0.0', function () {
	console.log('Explorer API server listening on port '+ appPort)
});

app.get('/initial', function(req, res) {
        updateLatestBlocks(0);
});
// ===================================================================================
// v2
function saveDifficult(diff) {

}
