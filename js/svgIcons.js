/**
 * @version $Id$
 * include SVG Icons via class="svgicon-ICONNAME"
 * icon
 * @author Rene Kanzler <rk (at) cosmomill (dot) de>
 */

var oIcons = {
  arrowright:
    'M11.166,23.963L22.359,17.5c1.43-0.824,1.43-2.175,0-3L11.166,8.037c-1.429-0.826-2.598-0.15-2.598,1.5v12.926C8.568,24.113,9.737,24.789,11.166,23.963z',
  arrowleft:
    'M20.834,8.037L9.641,14.5c-1.43,0.824-1.43,2.175,0,3l11.193,6.463c1.429,0.826,2.598,0.15,2.598-1.5V9.537C23.432,7.887,22.263,7.211,20.834,8.037z',
};

$(document).ready(function () {
  Object.keys(oIcons).forEach(function (sIconName) {
    $('.svgicon-' + sIconName).each(function (i) {
      oPaper = Raphael($(this)[0], 32, 32);
      var oIcon = oPaper.path(oIcons[sIconName]).attr({
        fill: '#333',
        //'transform': '...s.5'
      });
      console.log(oIcon);
      oIcon.paper.canvas.setAttribute('style', 'vertical-align: middle');
      oIcon.hover(
        function () {
          this.animate(
            {
              fill: '#333',
              stroke: '#ccc',
              'stroke-width': 1,
            },
            200
          );
        },
        function () {
          this.animate(
            {
              fill: '#333',
              stroke: 'none',
              'stroke-width': 0,
            },
            200
          );
        }
      );
    });
  });
});
