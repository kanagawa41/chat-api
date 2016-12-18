/**
 * Overlayクラス
 */
Overlay: {
    /**
     * コンストラクタ
     */
    Overlay = function() {
    }

    // prototype をローカル変数へ
    var p = Overlay.prototype;

    // FIXME 色と透明度は変えられるようにしたい
    // 静的なプロパティ
    var LAY_CSS = {
       "background" : "#000",
       "opacity"  : "0.5",
       "width"   : "100%",
       "height"  : 99999,
       "position"  : "fixed",
       "top"   : "0",
       "left"   : "0",
       "display"  : "none",
       "z-index"  : "50"
      };
    
    // パネルID (id、classに使われる)
    var filmPanelId = '';
    
    /**
     * パネルID設定
     */
    p.setFilmPanelId = function(filmPanelId){
        this.filmPanelId = filmPanelId;
    }

    // オーバーレイを表示する要素名
    var targetElement = '';
    
    /**
     * パネルID設定
     */
    p.setTargetElement = function(targetElement){
        this.targetElement = targetElement;
    }

    // パネルID (id、classに使われる)
    p.makeOverlayHtml = function(){
        return '<div class="' + this.filmPanelId + '" id="' + this.filmPanelId + '"></div>';
    }

    /**
     * パネルのみのオーバーレイを表示する
     */
    p.filmPanelFadeIn = function(){
      this.overlayInInit();
      $(this.makeOverlayHtml()).css(LAY_CSS).appendTo($(this.targetElement));
      
      $( "#" + this.filmPanelId ).fadeIn("slow");
    }

    /**
     * パネルのみのオーバーレイを非表示する
     */
    p.setFilmPanelFadeOut = function(){
        $( "#" + this.filmPanelId ).fadeOut("slow");
    }
    
    /**
     * ロード中のオーバーレイを表示する
     */
    p.loadingPanelFadeIn = function(){
      $('<div id="popup" style="display: none;">ポップアップコンテンツ</div>').appendTo($(this.targetElement));

      // ポップアップの画面中央になる様な位置関係を算出
      var left_positon = ($(this.targetElement).width()/2)-($("#popup").width()/2)
      var objectHeight = 200;
      var top_position = ($(this.targetElement).height() - objectHeight) / 2 + $(this.targetElement).scrollTop();

      this.overlayInInit();
      $(this.makeOverlayHtml()).css(LAY_CSS).appendTo($(this.targetElement));
      $( "#" + this.filmPanelId ).fadeIn("slow");
      
      // ポップアップのスタイルを定義
      $( "#popup" )
       .css("z-index","51")
       .css("position", "fixed")
       .css("top", top_position)
       .css("left", left_positon)
       .fadeIn("slow");
    }

    /**
     * ロード中のオーバーレイを非表示する
     */
    p.loadingPanelFadeOut = function(){
        this.setFilmPanelFadeOut();
    }
    
    /**
     * オーバーレイを表示する前の初期処理
     */
    p.overlayInInit = function(){
        this.filmPanelId = this.filmPanelId == null ? 'overlay_panel' : this.filmPanelId;
        this.targetElement = this.targetElement == null ? 'body' : this.targetElement;
    }

}