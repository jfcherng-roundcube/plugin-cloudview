// @see https://codepen.io/MAX_3/pen/RpxjLW

/*///// EXAMPLE //////////////////
node=psd.tree().descendants()[0]
var dataMask = getMask(); //Compressed data of the mask channel
console.info( dataMask );
var rgbaMask = parseMask( dataMask ); //Mask in rgba format
console.info( rgbaMask );
////////////////////////////////*/

var counter = 0;
var $set;

function psd(url) {
  var PSD = require('psd');

  PSD.fromURL(url).then(function (psd) {
    parsePsd(psd);
  });
}

function parsePsd(psd) {
  var node;
  var PsdW = psd.image.width();
  var PsdH = psd.image.height();
  $('#psd').empty();
  console.time('parse');
  $(psd.tree().descendants()).each(function (i, elem) {
    node = elem;
    draw(node);
  });
  console.timeEnd('parse');
  eachEnd();

  function getMask() {
    if (/*image.hasMask&&*/ node.layer.mask.width /*>0*/ && node.layer.mask.height /*>0*/) {
      var StartPos = node.layer.image.startPos;
      var EndPos;
      var inf = node.get('channelsInfo');
      for (var i = 0; i < inf.length; i++) {
        var elem = inf[i];
        if (elem.id == -2) {
          //-2 mask channel
          EndPos = StartPos + elem.length;
          break;
        } else {
          StartPos = StartPos + elem.length;
        }
      }
      return psd.file.data.slice(StartPos, EndPos);
    } else {
      console.error('oops, it seems there is no mask');
      return false;
    }
  }

  function parseMask(mask) {
    if (mask instanceof Uint8Array /*||Array.isArray(mask)*/) {
    } else {
      console.error('No array received');
      return false;
    }
    var МaskData = [];
    var ModeRAW = 0;
    var NotUseful = node.layer.mask.height * 2 + 2;
    if (mask[1] == 1) {
      //RLE
      for (var i = NotUseful; i < mask.length; i++) {
        var elem = mask[i];
        if (ModeRAW === 0) {
          //if(mask[i+1]===undefined)console.error("No next character!");
          if (elem < 128) {
            // 128?
            ModeRAW = +elem + 1; //Enable modeRAW to elem+1
          } else {
            var Repeat = 257 - elem; //257
            var Color = mask[i + 1];
            var r = 0;
            while (r < Repeat) {
              //Duplicate characters
              МaskData.push(0, 0, 0, Color);
              r++;
            }
            i++; //skip next step
          }
        } else {
          //ModeRAW
          МaskData.push(0, 0, 0, elem);
          ModeRAW--;
        }
      }
    } else if (mask[1] === 0) {
      //RAW
      МaskData = mask.join(',0,0,0,').slice(10).split(','); //bad
    } else {
      //zip?
      console.error('oops', mask[0], mask[1]);
    }
    return МaskData;
  }

  function draw(n) {
    var node = n;
    if (node.layer.mask.defaultColor === undefined) {
      // console.info(node.name,"- маски нет");
      return false;
    }
    var MaskW = node.layer.mask.width;
    var MaskH = node.layer.mask.height;
    var MaskT = node.layer.mask.top;
    var MaskL = node.layer.mask.left;
    var MaskC = node.get('mask').defaultColor;
    ++counter;
    var newC = document.createElement('canvas');
    newC.id = 'L' + counter;
    newC.setAttribute('data-name', node.name);
    document.getElementById('psd').appendChild(newC);
    var elem = document.getElementById('L' + counter);
    var ctx = elem.getContext('2d');
    elem.width = PsdW;
    elem.height = PsdH;
    if (MaskC !== 0) {
      ctx.fillRect(0, 0, PsdW, PsdH);
    }
    if (MaskW !== 0 && MaskH !== 0) {
      var checkMask = parseMask(getMask());
      if (checkMask) {
        var MaskImage = ctx.createImageData(MaskW, MaskH);
        if (MaskImage.data.set) {
          MaskImage.data.set(checkMask);
        } else {
          checkMask.forEach(function (val, i) {
            MaskImage.data[i] = val;
          });
        }
        ctx.putImageData(MaskImage, MaskL, MaskT);
      } else {
        console.error('oops');
      }
    }
  }
}

function eachEnd() {
  $set = $('canvas');
  $('#num')
    .attr('max', $('canvas').length - 1)
    .val($('canvas').length / 2);
  $('#name').text('Choose layer...');
  $('div,input').css('opacity', 1);
}

$(function () {
  $('body').on('click', 'canvas', function () {
    $('body').toggleClass('box');
    if (!$(this).hasClass('last1')) {
      var name = $(this).attr('data-name');
      $('#name').text('Layer: ' + name);
      $('.last3').removeClass('last3').addClass('last4');
      $('.last2').removeClass('last2').addClass('last3');
      $('.last1').removeClass('last1').addClass('last2');
      $(this).addClass('last1');
      var n = $set.index(this);
      $('#num').val(n);
    }
  });

  $('#num').change(function () {
    var num = $(this).val();
    $('.move').removeClass('move');
    $('.last3').removeClass('last3').addClass('last4');
    $('.last2').removeClass('last2').addClass('last3');
    $('.last1').removeClass('last1').addClass('last2');
    $('canvas').eq(num).addClass('last1');
    $('body').addClass('box');
  });

  $('#num').on('input', function () {
    var num = $(this).val();
    var name = $('canvas').eq(num).attr('data-name');
    $('#name').text('Layer: ' + name);
    $('.move').removeClass('move');
    $('canvas').eq(num).addClass('move');
  });
});
