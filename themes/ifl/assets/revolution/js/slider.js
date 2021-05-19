/*------------------------------------------------------------------------------*/
/*  Home_Page Slider
/*------------------------------------------------------------------------------*/

var revapi1,
  tpj;
jQuery(function() {
  tpj = jQuery;
  if(tpj("#rev_slider_1_1").revolution == undefined){
    revslider_showDoubleJqueryError("#rev_slider_1_1");
  }else{
    revapi1 = tpj("#rev_slider_1_1").show().revolution({
      sliderLayout:"fullwidth",
      visibilityLevels:"1240,1024,778,480",
      gridwidth:"1240,1024,778,480",
      gridheight:"550,500,450,350",
      minHeight:"",
      spinner:"spinner0",
      editorheight:"705,768,450,350",
      responsiveLevels:"1240,1240,778,480",
      disableProgressBar:"on",
      navigation: {
        mouseScrollNavigation:false,
        onHoverStop:false,
        touch: {
          touchenabled:true
        },
        arrows: {
          enable:true,
          tmp:"<div class=\"tp-title-wrap\"><div class=\"tp-arr-imgholder\"></div></div>",
          style:"zeus",
          hide_onmobile:true,
          hide_under:778,
          hide_onleave:true,
          left: {
            h_offset:30
          },
          right: {
            h_offset:30
          }
        }
      },
      fallbacks: {
        allowHTML5AutoPlayOnAndroid:true
      },
    });
  }
  
});