/**
 * HttpInputクラス
 */
HttpInput: {
    /**
     * コンストラクタ
     */
    HttpInput = function() {
    }

    // prototype をローカル変数へ
    var p = HttpInput.prototype;

    /**
     * GETパラメータを配列にセットして返却する。
     */
    HttpInput.getParams = function (){
        var url   = location.href;
        parameters    = url.split("?");
        if(parameters.length < 2) { return []};

        params   = parameters[1].split("&");
        var paramsArray = [];
        for ( i = 0; i < params.length; i++ ) {
            neet = params[i].split("=");
            paramsArray.push(neet[0]);
            paramsArray[neet[0]] = neet[1];
        }
        return paramsArray;
    }
};
