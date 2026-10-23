/* Ilustrasi produk (placeholder sampai foto asli tersedia) */
function rng(a){return()=>{a|=0;a=a+0x6D2B79F5|0;let t=Math.imul(a^a>>>15,1|a);t=t+Math.imul(t^t>>>7,61|t)^t;return((t^t>>>14)>>>0)/4294967296}}
const hx=h=>[1,3,5].map(k=>parseInt(h.slice(k,k+2),16));
const sh=(h,a)=>{const c=hx(h);return`rgb(${c.map(v=>Math.round(a>0?v+(255-v)*a:v*(1+a))).join(',')})`};
function poly(x,pts,fill){x.beginPath();x.moveTo(pts[0][0],pts[0][1]);for(let i=1;i<pts.length;i++)x.lineTo(pts[i][0],pts[i][1]);x.closePath();x.fillStyle=fill;x.fill()}
function shadow(x,on,b=26,oy=14){if(on){x.shadowColor='rgba(60,40,20,.28)';x.shadowBlur=b;x.shadowOffsetY=oy}else{x.shadowColor='transparent';x.shadowBlur=0;x.shadowOffsetY=0}}
function floor(x,cx,cy,rx,ry){x.save();x.filter='blur(14px)';x.fillStyle='rgba(60,40,20,.24)';x.beginPath();x.ellipse(cx,cy,rx,ry,0,0,7);x.fill();x.restore()}
function rr(x,X,Y,w,h,r){x.beginPath();if(x.roundRect)x.roundRect(X,Y,w,h,r);else x.rect(X,Y,w,h)}

function isoBox(x,o,ox=0,oy=0,noFloor){
  const W=o.W,D=o.D,H=o.H,b=o.body;
  const f=[ox+(D-W)*.433,oy+(H+.5*W+.5*D)/2];
  const rv=[.866*W,-.5*W],lv=[-.866*D,-.5*D];
  const R=[f[0]+rv[0],f[1]+rv[1]],L=[f[0]+lv[0],f[1]+lv[1]],B=[R[0]+lv[0],R[1]+lv[1]],u=p=>[p[0],p[1]-H];
  if(!noFloor)floor(x,f[0]+(rv[0]+lv[0])/2,f[1]-.25*(W+D)+8,(W+D)*.6,(W+D)*.26);
  poly(x,[L,f,u(f),u(L)],sh(b,-.16));poly(x,[f,R,u(R),u(f)],sh(b,-.04));poly(x,[u(f),u(R),u(B),u(L)],sh(b,.25));
  return{f,rv,lv,W,D,H,u};
}
function faceR(x,g,fn){x.save();x.transform(g.rv[0]/g.W,g.rv[1]/g.W,0,1,g.f[0],g.f[1]);fn();x.restore()}
function faceL(x,g,fn){x.save();x.transform(g.lv[0]/g.D,g.lv[1]/g.D,0,1,g.f[0],g.f[1]);fn();x.restore()}
function faceT(x,g,fn){const uf=g.u(g.f);x.save();x.transform(g.rv[0]/g.W,g.rv[1]/g.W,g.lv[0]/g.D,g.lv[1]/g.D,uf[0],uf[1]);fn();x.restore()}

function cyl(x,cx,cy,R,H,c,pat,r,core=.3){
  const ry=R*.32,top=cy-H/2,bot=cy+H/2;
  floor(x,cx,bot+8,R*1.15,ry*1.1);
  x.save();x.beginPath();x.moveTo(cx-R,top);x.lineTo(cx-R,bot);x.ellipse(cx,bot,R,ry,0,Math.PI,0,true);x.lineTo(cx+R,top);x.closePath();
  x.fillStyle=c;x.fill();x.clip();
  if(pat.includes('cork'))for(let i=0;i<520;i++){x.fillStyle=`rgba(110,60,20,${.12+r()*.3})`;x.beginPath();x.ellipse(cx-R+r()*2*R,top-ry+r()*(H+2*ry),1+r()*2.2,1+r()*1.4,0,0,7);x.fill()}
  if(pat.includes('gold')){x.fillStyle='rgba(255,236,170,.75)';x.fillRect(cx-R,top+H*.22,2*R,5);x.fillRect(cx-R,bot-H*.22,2*R,5)}
  if(pat.includes('flex')){const cols=['#F4F1EA','#E9C46A','#E76F51'];for(let k=0;k<6;k++){x.fillStyle=cols[k%3];x.globalAlpha=.8;x.fillRect(cx-R+k*(R/3)+4,top+H*.2,R/3-8,H*.6)}x.globalAlpha=1}
  if(pat.includes('foil')){for(let k=0;k<8;k++){x.fillStyle=`rgba(255,255,255,${k%2?.25:.08})`;x.fillRect(cx-R,top+k*H/8,2*R,H/16)}}
  const g2=x.createLinearGradient(cx-R,0,cx+R,0);g2.addColorStop(0,'rgba(0,0,0,.3)');g2.addColorStop(.36,'rgba(255,255,255,.22)');g2.addColorStop(.6,'rgba(0,0,0,0)');g2.addColorStop(1,'rgba(0,0,0,.34)');
  x.fillStyle=g2;x.fillRect(cx-R,top-ry,2*R,H+2*ry+ry);x.restore();
  x.fillStyle=sh(c,.55);x.beginPath();x.ellipse(cx,top,R,ry,0,0,7);x.fill();
  x.strokeStyle='rgba(0,0,0,.07)';x.lineWidth=1;
  for(let k=1;k<6;k++){const s=core+(1-core)*k/6;x.beginPath();x.ellipse(cx,top,R*s,ry*s,0,0,7);x.stroke()}
  x.fillStyle='#A0805A';x.beginPath();x.ellipse(cx,top,R*core,ry*core,0,0,7);x.fill();
  x.fillStyle='#3A2C20';x.beginPath();x.ellipse(cx,top+1,R*core*.7,ry*core*.7,0,0,7);x.fill();
}

const K={};
K.box=(x,r,o,C)=>{const g=isoBox(x,o),b=o.band;
  faceR(x,g,()=>{x.fillStyle=b;x.fillRect(0,-g.H*.66,g.W,g.H*.16);
    if(o.mark==='win'){x.fillStyle='#E8D6BC';rr(x,g.W*.2,-g.H*.42,g.W*.6,g.H*.26,10);x.fill();x.fillStyle='#D59A5E';for(let i=0;i<3;i++){x.beginPath();x.arc(g.W*(.34+i*.16),-g.H*.29,7,0,7);x.fill()}}
    if(o.mark==='plus'){x.fillRect(g.W/2-6,-g.H*.42,12,40);x.fillRect(g.W/2-20,-g.H*.42+14,40,12)}
    if(o.mark==='dot'){x.strokeStyle=b;x.lineWidth=2;x.beginPath();x.arc(g.W/2,-g.H*.34,Math.min(g.W,g.H)*.17,0,7);x.stroke()}
    x.fillStyle='rgba(25,24,23,.5)';x.font='600 9px Consolas,monospace';x.fillText(C.s.toUpperCase(),8,-8)});
  faceL(x,g,()=>{x.fillStyle=sh(b,-.18);x.fillRect(0,-g.H*.66,g.D,g.H*.16)});
};
K.pack=(x,r,o)=>{
  const one=(ox,oy,body)=>{const g=isoBox(x,{W:78,D:42,H:160,body},ox,oy);
    faceR(x,g,()=>{x.fillStyle=o.band;x.fillRect(0,-g.H*.56,g.W,g.H*.3);x.strokeStyle='rgba(0,0,0,.25)';x.beginPath();x.moveTo(0,-g.H*.8);x.lineTo(g.W,-g.H*.8);x.stroke();
      x.fillStyle=body;x.beginPath();x.arc(g.W/2,-g.H*.41,14,0,7);x.fill();x.fillStyle=o.band;x.font='italic 700 11px Georgia';x.textAlign='center';x.fillText('TJ',g.W/2,-g.H*.41+4);x.textAlign='left'});
    faceL(x,g,()=>{x.fillStyle=sh(o.band,-.15);x.fillRect(0,-g.H*.56,g.D,g.H*.3)})};
  one(-70,-35,'#2E4A3F');one(45,15,o.body);
};
K.carton=(x,r,o)=>{
  const body={B:'#C79A62',C:'#E8DFCF',E:'#C79A62',BC:'#E8DFCF'}[o.fl];
  const g=isoBox(x,{W:160,D:120,H:115,body},10,-50);
  faceT(x,g,()=>{x.fillStyle='rgba(120,80,40,.3)';x.fillRect(0,g.D*.42,g.W,g.D*.16)});
  faceR(x,g,()=>{x.fillStyle='rgba(40,30,20,.6)';x.font='700 13px Consolas,monospace';x.fillText(o.fl+'-FLUTE',12,-14);
    x.strokeStyle='rgba(40,30,20,.6)';x.lineWidth=2.5;for(const i of[0,1]){const ax=g.W-40+i*14;x.beginPath();x.moveTo(ax,-30);x.lineTo(ax,-60);x.moveTo(ax-6,-52);x.lineTo(ax,-60);x.lineTo(ax+6,-52);x.stroke()}});
  const layers=o.fl==='BC'?[6,9]:[{E:3.5,B:6,C:9}[o.fl]];
  const wd=250,ht=layers.reduce((s,a)=>s+a*2+5,0)+3;
  x.save();x.translate(-10,165);x.rotate(-.035);
  shadow(x,1,18,10);x.fillStyle=body;x.fillRect(-wd/2,-ht,wd,ht);shadow(x,0);
  x.strokeStyle=sh(body,-.4);x.lineWidth=2;let y=-ht+1.5;
  const line=yy=>{x.beginPath();x.moveTo(-wd/2,yy);x.lineTo(wd/2,yy);x.stroke()};line(y);
  for(const a of layers){const per=a*3.2;x.beginPath();for(let px=-wd/2;px<=wd/2;px+=2){const yy=y+2.5+a+Math.sin(px/per*Math.PI)*a;px===-wd/2?x.moveTo(px,yy):x.lineTo(px,yy)}x.stroke();y+=a*2+5;line(y)}
  x.fillStyle='rgba(40,30,20,.65)';x.font='600 11px Consolas,monospace';x.fillText('PENAMPANG '+o.fl+'-FLUTE',-wd/2,-ht-10);
  x.restore();
};
K.roll=(x,r,o)=>{cyl(x,40,-40,110,180,o.c,o.pat,r,.28);cyl(x,-105,105,58,80,o.c,o.pat,r,.34)};
K.rolls3=(x,r)=>{cyl(x,-90,-55,64,150,'#C9D3D8','foil',r,.34);cyl(x,40,-30,64,110,'#E8E2D4','plain',r,.34);cyl(x,-20,110,64,60,'#B4BEC6','foil',r,.34)};
K.tape=(x,r)=>{cyl(x,45,-55,118,48,'#F4F1E8','plain',r,.55);cyl(x,-75,75,86,42,'#E7D6AE','plain',r,.55);
  x.fillStyle='rgba(244,241,232,.95)';shadow(x,1,10,5);poly(x,[[100,85],[190,150],[176,168],[86,103]],'#F4F1E8');shadow(x,0)};
K.rods=(x,r,o)=>{const L=o.len,d=26,N=o.tip?6:10;floor(x,0,150,210,42);
  x.save();x.translate(0,20);x.rotate(-.42);
  for(let i=0;i<N;i++){x.save();
    const yy=o.tip?(i-N/2)*(d+5):((i>>1)-2.5)*(d+5),xx=o.tip?(r()-.5)*60:(i%2?-80:80)+(r()-.5)*16;
    x.translate(xx,yy);x.rotate((r()-.5)*.08);
    shadow(x,1,12,8);rr(x,-L/2,-d/2,L,d,d/2);x.fillStyle='#FAF8F2';x.fill();shadow(x,0);
    x.save();rr(x,-L/2,-d/2,L,d,d/2);x.clip();
    if(o.tip){x.fillStyle='#D9A35B';x.fillRect(L/2-75,-d/2,75,d);for(let k=0;k<40;k++){x.fillStyle='rgba(110,60,20,.35)';x.fillRect(L/2-75+r()*75,-d/2+r()*d,1.6,1.6)}
      x.fillStyle='#8B5A2B';x.beginPath();x.ellipse(-L/2+4,0,5,d/2,0,0,7);x.fill()}
    else{x.fillStyle='#ECE5D4';x.beginPath();x.ellipse(L/2-4,0,5,d/2,0,0,7);x.fill()}
    const g=x.createLinearGradient(0,-d/2,0,d/2);g.addColorStop(0,'rgba(255,255,255,.5)');g.addColorStop(.5,'rgba(0,0,0,0)');g.addColorStop(1,'rgba(0,0,0,.2)');x.fillStyle=g;x.fillRect(-L/2,-d/2,L,d);
    x.restore();x.restore()}
  x.restore();
};
K.sheet=(x,r,o,C)=>{
  for(let k=0;k<4;k++){x.save();x.rotate((k-2)*.06);x.translate((k-2)*12,(k-2)*-6);
    shadow(x,1,18,8);x.fillStyle='#FBFAF6';x.fillRect(-120,-160,240,320);shadow(x,0);
    if(k===3){
      if(!o.tp){['#00A3E0','#E6007E','#FFED00','#1A1A1A'].forEach((c,i)=>{x.fillStyle=c;x.fillRect(-100+i*26,-142,20,20)});
        const g=x.createLinearGradient(-100,-110,100,40);g.addColorStop(0,C.ac);g.addColorStop(1,'#E9C46A');x.fillStyle=g;x.fillRect(-100,-110,200,150);
        x.fillStyle='rgba(255,255,255,.85)';x.beginPath();x.arc(40,-40,38,0,7);x.fill();
        x.fillStyle='rgba(25,24,23,.75)';x.fillRect(-100,60,150,10);x.fillStyle='rgba(25,24,23,.25)';[80,94,108].forEach((y,i)=>x.fillRect(-100,y,200-i*40,6));
        x.strokeStyle='rgba(25,24,23,.5)';x.lineWidth=1;[[-120,-160],[120,-160],[-120,160],[120,160]].forEach(([a,b])=>{x.beginPath();x.moveTo(a-Math.sign(a)*0,b-Math.sign(b)*14);x.lineTo(a,b+Math.sign(b)*14);x.moveTo(a-Math.sign(a)*14,b);x.lineTo(a+Math.sign(a)*14,b);x.stroke()});
      }else{x.fillStyle=C.ac;x.fillRect(-108,-148,216,296);x.fillStyle='#F2B94B';x.beginPath();x.arc(60,-80,60,0,7);x.fill();
        x.fillStyle='#FBFAF6';x.font='italic 400 64px Georgia';x.fillText('Print',-92,40);x.font='600 11px Consolas,monospace';x.fillText('DIGITAL · TANPA PLAT',-90,70);
        x.fillStyle='rgba(255,255,255,.5)';x.fillRect(-90,110,120,6)}
    }x.restore()}
};
K.sticker=(x,r,o)=>{const c=o.c;
  const st=(fn,col,rot,tx,ty,extra)=>{x.save();x.translate(tx,ty);x.rotate(rot);fn();shadow(x,1,18,10);x.fillStyle='#fff';x.fill();shadow(x,0);x.save();x.scale(.88,.88);fn();x.fillStyle=col;x.fill();if(extra)extra();x.restore();x.restore()};
  st(()=>rr(x,-105,-62,210,124,28),c[1],.12,40,80,()=>{x.fillStyle='rgba(25,24,23,.7)';x.font='italic 40px Georgia';x.fillText('label',-70,14)});
  st(()=>{x.beginPath();x.arc(0,0,86,0,7)},c[0],-.1,-60,-60,()=>{x.fillStyle='#fff';x.font='italic 700 60px Georgia';x.textAlign='center';x.fillText('TJ',0,20);x.textAlign='left'});
  st(()=>{x.beginPath();for(let i=0;i<10;i++){const a=-Math.PI/2+i*Math.PI/5,rd=i%2?32:70;x.lineTo(Math.cos(a)*rd,Math.sin(a)*rd)}x.closePath()},c[2],.2,100,-110);
  x.save();x.translate(40,80);x.rotate(.12);shadow(x,1,8,4);poly(x,[[105,22],[105,62],[70,62]],'#F7F5F0');shadow(x,0);x.restore();
};
K.shirt=(x,r,o)=>{const p=new Path2D('M-45,-150 Q0,-122 45,-150 L130,-112 L168,-32 L116,-12 L100,-58 L100,150 L-100,150 L-100,-58 L-116,-12 L-168,-32 L-130,-112 Z');
  shadow(x,1,26,14);x.fillStyle=o.c;x.fill(p);shadow(x,0);
  x.save();x.clip(p);const g=x.createLinearGradient(-170,0,170,0);g.addColorStop(0,'rgba(0,0,0,.18)');g.addColorStop(.5,'rgba(255,255,255,.12)');g.addColorStop(1,'rgba(0,0,0,.2)');x.fillStyle=g;x.fillRect(-170,-160,340,320);
  x.fillStyle='#F2B94B';x.fillRect(-170,-60,340,10);x.restore();
  x.strokeStyle=sh(o.c,.55);x.lineWidth=8;x.beginPath();x.moveTo(-45,-150);x.quadraticCurveTo(0,-118,45,-150);x.stroke();
  x.fillStyle='#FBFAF6';x.font='400 120px Georgia';x.textAlign='center';x.fillText('10',0,90);x.font='600 13px Consolas,monospace';x.fillText('TOP PRINTING',0,-10);x.textAlign='left';
};
K.cards=(x,r,o,C)=>{
  x.save();x.rotate(-.12);shadow(x,1,20,10);x.fillStyle='#EADFCB';x.fillRect(-160,-150,300,190);shadow(x,0);
  x.strokeStyle='rgba(120,90,50,.35)';x.lineWidth=2;x.beginPath();x.moveTo(-160,-150);x.lineTo(-10,-50);x.lineTo(140,-150);x.stroke();x.restore();
  [[C.ac,-30,30,-.08,'#FBFAF6'],['#FBFAF6',20,90,.1,'#191817']].forEach(([bg,tx,ty,rt,fg])=>{x.save();x.translate(tx,ty);x.rotate(rt);shadow(x,1,16,8);x.fillStyle=bg;x.fillRect(-110,-62,220,124);shadow(x,0);
    x.fillStyle=fg;x.beginPath();x.arc(-80,-30,14,0,7);x.fill();x.font='italic 22px Georgia';x.fillText('Nama Anda',-90,20);x.globalAlpha=.5;x.fillRect(-90,32,110,4);x.fillRect(-90,42,80,4);x.globalAlpha=1;x.restore()});
};
K.banner=(x,r,o,C)=>{
  floor(x,0,190,150,20);rr(x,-112,172,224,16,8);x.fillStyle='#3A3A3A';x.fill();
  shadow(x,1,24,12);x.fillStyle='#FBFAF6';x.fillRect(-95,-200,190,372);shadow(x,0);
  x.fillStyle=C.ac;x.fillRect(-95,-200,190,170);x.fillStyle='#F2B94B';x.beginPath();x.arc(50,-60,70,Math.PI,0);x.fill();
  x.fillStyle='#FBFAF6';x.font='italic 400 46px Georgia';x.fillText('Promo',-78,-120);
  x.fillStyle='rgba(25,24,23,.75)';x.font='600 12px Consolas,monospace';x.fillText('BANNER · POSTER',-78,10);
  x.fillStyle='rgba(25,24,23,.2)';[34,48,62].forEach((y,i)=>x.fillRect(-78,y,150-i*30,6));
  x.fillStyle=C.ac;x.fillRect(-78,120,90,26);
};
K.machine=(x,r,o,C)=>{const ac=C.ac,body='#ECECE6',t=o.t;
  floor(x,10,150,240,36);
  if(t==='maker'||t==='maker2'){poly(x,[[-150,-62],[-60,-62],[-40,-160],[-170,-160]],'#D8D8D0');poly(x,[[-170,-160],[-40,-160],[-30,-172],[-160,-172]],'#E8E8E2')}
  if(t==='maker2'){x.fillStyle='#BFC2BE';x.fillRect(52,-120,14,60);x.strokeStyle='#8E9290';x.lineWidth=6;x.beginPath();x.arc(60,-130,40,0,7);x.stroke();for(let k=0;k<6;k++){const a=k*Math.PI/3;x.beginPath();x.moveTo(60,-130);x.lineTo(60+Math.cos(a)*40,-130+Math.sin(a)*40);x.stroke()}}
  if(t==='packer'){poly(x,[[40,-62],[130,-62],[130,-180],[40,-180]],'#E2E2DA');poly(x,[[130,-62],[150,-76],[150,-194],[130,-180]],'#CFCFC6');poly(x,[[40,-180],[130,-180],[150,-194],[60,-194]],'#F2F2EC');x.fillStyle=ac;x.fillRect(40,-110,90,8)}
  poly(x,[[-170,-40],[150,-40],[150,120],[-170,120]],body);
  poly(x,[[-170,-40],[150,-40],[180,-62],[-140,-62]],'#F6F6F1');
  poly(x,[[150,-40],[180,-62],[180,98],[150,120]],'#D2D2C9');
  x.fillStyle=ac;x.fillRect(-170,96,320,10);poly(x,[[150,96],[180,74],[180,84],[150,106]],sh(ac,-.2));
  rr(x,-150,-22,86,54,6);x.fillStyle='#263033';x.fill();
  x.strokeStyle='#7FE0A0';x.lineWidth=2;x.beginPath();for(let k=0;k<8;k++){const px=-142+k*10,py=10-r()*22;k?x.lineTo(px,py):x.moveTo(px,py)}x.stroke();
  [['#D64933',-48],['#3BAA6B',-30],[ac,-12]].forEach(([c,px])=>{x.fillStyle=c;x.beginPath();x.arc(px,-4,6,0,7);x.fill()});
  x.strokeStyle='rgba(0,0,0,.12)';x.lineWidth=1.5;for(let yy=-14;yy<70;yy+=10){x.beginPath();x.moveTo(50,yy);x.lineTo(130,yy);x.stroke()}
  rr(x,-150,46,120,40,4);x.stroke();
  x.fillStyle='#191817';x.font='400 30px Georgia';x.fillText(o.l,-20,30);x.fillStyle='rgba(25,24,23,.5)';x.font='600 9px Consolas,monospace';x.fillText('MAXTECH SOLUTION',-20,46);
  if(t==='wrap'||t==='wrap2'){const R=t==='wrap2'?26:18,w=t==='wrap2'?150:110;x.fillStyle='#C9D7DC';x.fillRect(-60,-62-R*2,w,R*2);const g=x.createLinearGradient(0,-62-R*2,0,-62);g.addColorStop(0,'rgba(255,255,255,.6)');g.addColorStop(1,'rgba(0,0,0,.15)');x.fillStyle=g;x.fillRect(-60,-62-R*2,w,R*2);x.fillStyle='#9FB2B8';x.beginPath();x.ellipse(-60+w,-62-R,R*.4,R,0,0,7);x.fill()}
  if(t==='stamp'){x.fillStyle='#A9ADAA';x.fillRect(150,-30,14,110);x.fillStyle=ac;x.fillRect(136,80,42,14)}
  if(t==='boxer'){poly(x,[[-120,-62],[-60,-62],[-50,-100],[-110,-100]],'#C79A62');poly(x,[[-60,-62],[0,-62],[10,-96],[-50,-100]],'#B88A55')}
  rr(x,-215,120,440,16,8);x.fillStyle='#2F3233';x.fill();x.fillStyle='#55595A';for(let k=-200;k<220;k+=30){x.beginPath();x.arc(k,128,4,0,7);x.fill()}
  const item=(px,W,D,H,body,deco)=>{const g=isoBox(x,{W,D,H,body},px,120-(H+.5*W+.5*D)/2,1);if(deco)deco(g)};
  if(t==='maker'||t==='maker2'){for(let k=0;k<7;k++){x.save();x.translate(150+k*9,106-(k%2)*8);rr(x,0,-4,40,8,4);x.fillStyle='#FAF8F2';x.fill();x.fillStyle='#D9A35B';x.fillRect(28,-4,12,8);x.restore()}}
  if(t==='packer'||t==='stamp'||t==='wrap')[165,200].forEach(px=>item(px,26,16,40,'#B8322A',g=>{if(t==='stamp')faceT(x,g,()=>{x.fillStyle='#F3E6CF';x.fillRect(g.W*.4,0,g.W*.2,g.D)});if(t==='wrap')faceR(x,g,()=>{x.fillStyle='rgba(255,255,255,.35)';x.fillRect(0,-g.H,g.W*.4,g.H)})}));
  if(t==='wrap2')item(185,70,28,30,'#B8322A',g=>faceR(x,g,()=>{x.fillStyle='rgba(255,255,255,.35)';x.fillRect(0,-g.H,g.W*.3,g.H)}));
  if(t==='boxer')[170,215].forEach(px=>item(px,40,34,38,'#C79A62',g=>faceT(x,g,()=>{x.fillStyle='rgba(120,80,40,.35)';x.fillRect(0,g.D*.4,g.W,g.D*.2)})));
};

function draw(p,w,h,v=0){
  const cv=document.createElement('canvas');cv.width=w;cv.height=h;const x=cv.getContext('2d');const C=CO[p.co],r=rng(p.seed+v*97);
  let g=x.createLinearGradient(0,0,0,h);const b0=v===2?C.bg[1]:C.bg[0],b1=v===2?sh(C.bg[1],-.06):C.bg[1];g.addColorStop(0,b0);g.addColorStop(1,b1);x.fillStyle=g;x.fillRect(0,0,w,h);
  g=x.createRadialGradient(w*.25,h*.15,0,w*.25,h*.15,w*.9);g.addColorStop(0,'rgba(255,255,255,.45)');g.addColorStop(1,'rgba(255,255,255,0)');x.fillStyle=g;x.fillRect(0,0,w,h);
  x.fillStyle='rgba(25,24,23,.05)';x.fillRect(0,h*.78,w,1);
  x.save();x.translate(w/2,h*.55);const s=w/480*(v===1?1.35:.92);x.scale(s,s);if(v===1)x.translate(-25,15);if(v===2)x.rotate(-.05);
  K[p.kind](x,r,p.o,C,p);shadow(x,0);x.restore();
  const id=x.getImageData(0,0,w,h),d=id.data;for(let i=0;i<d.length;i+=4){const n=(Math.random()-.5)*12;d[i]+=n;d[i+1]+=n;d[i+2]+=n}x.putImageData(id,0,0);
  g=x.createRadialGradient(w/2,h/2,w*.35,w/2,h/2,w*.9);g.addColorStop(0,'rgba(0,0,0,0)');g.addColorStop(1,'rgba(40,25,10,.18)');x.fillStyle=g;x.fillRect(0,0,w,h);
  return cv.toDataURL('image/jpeg',.86);
}
function drawPlate(w,h){
  const cv=document.createElement('canvas');cv.width=w;cv.height=h;const x=cv.getContext('2d');
  x.fillStyle='#F3EEE6';x.fillRect(0,0,w,h);x.fillStyle='#191817';x.textAlign='center';
  x.font=`400 ${w*.42}px Georgia`;x.fillText('TJ',w/2,h*.5);
  x.font=`600 ${w*.035}px Consolas,monospace`;x.fillText('THAMRIN JAYA GROUP',w/2,h*.62);
  x.fillRect(w*.35,h*.66,w*.3,1);x.fillStyle='#8A847B';x.font=`italic ${w*.045}px Georgia`;x.fillText('Pasuruan · Jawa Timur',w/2,h*.72);
  ORDER.forEach((k,i)=>{x.fillStyle=CO[k].ac;x.beginPath();x.arc(w/2+(i-2.5)*w*.06,h*.82,w*.014,0,7);x.fill()});
  return cv.toDataURL('image/jpeg',.9);
}
