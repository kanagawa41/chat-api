/**
 * メッセージヒストリークラス
 * メッセージを指定数内になるように管理、保存する。
 * ※strage.jsのインクルードが必須
 */
MessageHistory: {
    /**
     * コンストラクタ
     */
    MessageHistory = function(storeMessageCount) {
    	if(!storeMessageCount){
	    	STORE_MESSAGE_COUNT = storeMessageCount; // 保持数を設定する
		}
    }

    // prototype をローカル変数へ
    var p = MessageHistory.prototype;

    // メッセージの格納キー
	var STORE_KEY = 'message_history';

    // 保持するメッセージ数
	var STORE_MESSAGE_COUNT = 9999;

    /**
     * メッセージ数を取得する
     */
    MessageHistory.setStoreKey = function (storeKey){
        STORE_KEY = storeKey;
    }

    /**
     * メッセージ数を取得する
     */
    MessageHistory.countMessage = function (){
        var messages = store.get(STORE_KEY);
        
        return messages ? messages.length : 0;
	}

    /**
     * 最も古いメッセージを返却する。
     * [0,1,2,3,4]の場合は、0が返却される。
     */
    MessageHistory.getOldestMessage = function (){
        var messages = store.get(STORE_KEY);
        
        return !messages || messages.length == 0 ? null : messages.slice(0, 1);
	}

    /**
     * 新しいメッセージを返却する。
     * [0,1,2,3,4]の場合は、4が返却される。
     */
    MessageHistory.getRecentMessage = function (){
        var messages = store.get(STORE_KEY);
        
        return !messages || messages.length == 0 ? null : messages.slice(-1);
	}
	
    /**
     * メッセージを配列の前方に追加する。
     * もしメッセージが規定値以上の場合は追加しない。
     * [0,1,2,3,4]で5を追加する場合は、[5,0,1,2,3,4]となる。
     * 引数はメッセージ配列
     */
    MessageHistory.prepend = function (message){
        var messages = store.get(STORE_KEY);
        
        if(!messages){
        	messages = [];
        }

		// メッセージが指定数を超過
        if(messages.length > STORE_MESSAGE_COUNT){ return; }

    	messages.unshift(message);
        store.set(STORE_KEY, messages);
    }

    /**
     * メッセージを配列の後方に追加する。
     * [0,1,2,3,4]で5を追加する場合は、[0,1,2,3,4,5]となる。
     * 引数はメッセージ配列
     */
    MessageHistory.append = function (message){
        var messages = store.get(STORE_KEY);
        
        if(!messages){
        	messages = [];
        }

        if(messages.length < STORE_MESSAGE_COUNT){ // メッセージが指定数以下
        	messages.push(message);
        } else {
            messages = messages.slice(1, STORE_MESSAGE_COUNT); // 前方を切りだす
        	messages.push(message);
        }

        store.set(STORE_KEY, messages);
    }

    /**
     * 指定のmessage_idより過去のメッセージを指定個数分取得する。
     * [0,1,2,3,4]で3から2つの場合は、[1,2]が返却される。
     */
    MessageHistory.findMessage = function (message_id){
        var messages = store.get(STORE_KEY);
        
        if(!messages){
        	return [];
        }

		messages.reverse(); // [1,2,3,4,5]→[5,4,3,2,1]
		
		var targetMessage = null;
		$.each(messages, function(key, val) {
			if(val.message_id == message_id){
				targetMessage = val;
				return false;
			}
		});

        if(!targetMessage){ return []; }
        
        return targetMessage;
    }

    /**
     * 指定のmessage_idより過去のメッセージを指定個数分取得する。
     * [0,1,2,3,4]で指定message_idが3、個数は2の場合は、[1,2]が返却される。
     */
    MessageHistory.findPastMessage = function (message_id, count){
        var messages = store.get(STORE_KEY);
        
        if(!messages){
        	return [];
        }

		messages.reverse(); // [1,2,3,4,5]→[5,4,3,2,1]
		
		var messageIndex = null;
		$.each(messages, function(key, val) {
			if(val.message_id == message_id){
				messageIndex = key;
				return false;
			}
		});

        if(!messageIndex){ return []; }

		var beginPosition = messageIndex + 1;
		var endPosition = (messageIndex + 1) + count;

		//モトの順番に戻し返却する。
		return messages.slice(beginPosition, endPosition).reverse();
    }

    /**
     * メッセージ配列を返却する。
     */
    MessageHistory.getMessages = function (){
        var messages = store.get(STORE_KEY);
        return messages ? messages : [];
    }

    /**
     * メッセージ配列をリセットする。
     */
    MessageHistory.resetMessages = function (){
        return store.remove(STORE_KEY);
    }
};