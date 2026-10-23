const $=s=>document.querySelector(s),$$=s=>[...document.querySelectorAll(s)];
const RM=matchMedia('(prefers-reduced-motion: reduce)').matches;
const pad=n=>String(n).padStart(2,'0');
const N=P.length;
const dot=k=>`<i style="background:${CO[k].ac}"></i>`;
const waLink=(k,msg)=>`https://wa.me/${CO[k].wa}?text=${encodeURIComponent(msg)}`;

/* ---------- views ---------- */
let view='hang';
function pill(){const b=$('.nav button.on');if(!b)return;const p=$('#pill');p.style.left=b.offsetLeft+'px';p.style.width=b.offsetWidth+'px'}
function stagger(el){el.querySelectorAll('.rise').forEach((r,i)=>{r.classList.remove('go');r.style.animationDelay=(i*70)+'ms';void r.offsetWidth;r.classList.add('go')})}
function go(v){
  $$('.view').forEach(s=>s.classList.remove('on'));const el=v==='plate'?$('#plate'):$('#v-'+v);el.classList.add('on');stagger(el);view=v;
  if(v!=='plate'){$$('.nav button').forEach(b=>b.classList.toggle('on',b.dataset.go===v));pill()}
}
$$('[data-go]').forEach(b=>b.addEventListener('click',()=>go(b.dataset.go)));
addEventListener('resize',()=>{pill();layout()});

/* ---------- build ---------- */
let IMG=[],TH=[];
const chipsHTML=(all=true)=>(all?'<button data-f="all" class="on">Semua</button>':'')+ORDER.map(k=>`<button data-f="${k}">${dot(k)}${CO[k].s}</button>`).join('');
function build(){
  IMG=P.map(p=>draw(p,480,600));TH=P.map(p=>draw(p,300,375,1));
  // display
  $('#world').innerHTML='<div class="wire" id="wire"></div>'+P.map(p=>`<div class="hang" data-i="${p.i}"><div class="sway" style="animation-delay:${-p.i*0.9}s;animation-duration:${5+p.i%3}s">
    <div class="strings"><i></i><i></i></div><div class="clip" style="left:calc(22% - 6px)"></div><div class="clip" style="right:calc(22% - 6px)"></div>
    <div class="frame" data-open="${p.i}"><img src="${IMG[p.i]}" alt="${p.t}" draggable="false"><div class="cap"><span>No.${pad(p.i+1)}</span><span>${CO[p.co].s.toUpperCase()}</span></div></div></div></div>`).join('');
  $('#hchips').innerHTML=`<div class="chips">${chipsHTML(false)}</div>`;
  $$('#hchips button').forEach(b=>b.onclick=()=>{target=P.findIndex(p=>p.co===b.dataset.f);hide()});
  $('#ticks').innerHTML=P.map((p,k)=>`<button data-t="${p.i}" class="${k&&P[k-1].co!==p.co?'gap':''}" aria-label="${p.t}"></button>`).join('');
  $$('#ticks button').forEach(b=>b.onclick=()=>{target=+b.dataset.t});
  // katalog
  $('#rows').innerHTML=P.map(p=>`<tr data-i="${p.i}" data-co="${p.co}"><td>${pad(p.i+1)}</td><td>${p.t}</td><td><b style="background:${CO[p.co].ac}"></b>${CO[p.co].s}</td><td>${CO[p.co].cat}</td><td>${p.tech}</td></tr>`).join('');
  $('#kchips').innerHTML=chipsHTML();
  // gallery
  const items=[];P.forEach(p=>{items.push({p,src:IMG[p.i]});items.push({p,src:TH[p.i]})});
  const rr=rng(11);for(let i=items.length-1;i>0;i--){const j=rr()*(i+1)|0;[items[i],items[j]]=[items[j],items[i]]}
  const cols=10;TW=140+cols*360;TH_=130+Math.ceil(items.length/cols)*370;
  $('#table').style.width=TW+'px';$('#table').style.height=TH_+'px';
  $('#table').innerHTML=items.map((it,k)=>{const col=k%cols,row=k/cols|0,w=180+rr()*70,rot=(rr()-.5)*7;
    return`<div class="slide" data-i="${it.p.i}" data-co="${it.p.co}" style="left:${100+col*360+rr()*60}px;top:${100+row*370+rr()*50}px;width:${w}px;--r:rotate(${rot}deg);transform:var(--r)"><img src="${it.src}" alt="${it.p.t}" draggable="false"><span>${CO[it.p.co].s} · ${it.p.t}</span></div>`}).join('');
  $('#gchips').innerHTML=chipsHTML();
  // grup
  $('#plateimg').src=drawPlate(360,450);
  $('#aboutprint').src=IMG[9];$('#apcap').textContent='No.10';
  $('#cats').innerHTML=CATS.map(([c,ks])=>`<div class="cat"><div><h3>${c}</h3><small class="mono">${ks.reduce((s,k)=>s+P.filter(p=>p.co===k).length,0)} produk</small></div><div class="cos">${ks.map(k=>`<button data-k="${k}">${dot(k)}${CO[k].s} ›</button>`).join('')}</div></div>`).join('');
  $$('#cats button').forEach(b=>b.onclick=()=>{go('index');filterRows(b.dataset.k)});
  // kontak
  $('#ctbg').style.backgroundImage=`url(${IMG[4]})`;
  $('#selco').innerHTML=ORDER.map(k=>`<option value="${k}">${CO[k].s} · ${CO[k].cat}</option>`).join('');
  fillProducts('sj');
  $('#clist').innerHTML=ORDER.map(k=>{const c=CO[k];return`<div class="co"><h4>${dot(k)}${c.n}</h4><p>${c.cat} · sejak ${c.y}<br>${c.tel} · ${c.addr}</p><a href="${c.url}">Situs ↗</a></div>`}).join('');
}
function fillProducts(k,sel){$('#selp').innerHTML='<option value="">Belum tahu, mau konsultasi dulu</option>'+P.filter(p=>p.co===k).map(p=>`<option value="${p.i}" ${p.i===sel?'selected':''}>${p.t}</option>`).join('')}
$('#selco').addEventListener('change',e=>{fillProducts(e.target.value);const f=P.find(p=>p.co===e.target.value);$('#ctbg').style.backgroundImage=`url(${IMG[f.i]})`});
$('#selp').addEventListener('change',e=>{if(e.target.value!=='')$('#ctbg').style.backgroundImage=`url(${IMG[+e.target.value]})`});

/* ---------- display 3D ---------- */
let XS,ZS,cur=0,target=0,mx=0,my=0,smx=0,smy=0,idle=0;
function layout(){
  const m=innerWidth<900;XS=m?44:66;ZS=m?360:430;
  const fw=parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--fw'))||290;
  $$('.hang').forEach(h=>{const i=+h.dataset.i;h.style.transform=`translate3d(${i*XS-fw/2}px,${-fw*1.02}px,${-i*ZS}px)`});
  const len=Math.hypot(XS,ZS),ang=Math.atan2(ZS,XS)*180/Math.PI,w=$('#wire');
  if(w){w.style.width=len*(N+3)+'px';w.style.top=(-fw*1.02)+'px';w.style.transform=`translate3d(${-XS*2}px,0,${ZS*2}px) rotateY(${ang}deg)`}
}
const hangs=()=>frame.h||(frame.h=$$('.hang'));
function frame(){
  idle+=.012;cur+=(target-cur)*(RM?1:.085);smx+=(mx-smx)*.06;smy+=(my-smy)*.06;
  const fl=RM?0:Math.sin(idle)*.6;
  $('#world').style.transform=`translateZ(-120px) rotateX(${(-smy*4+fl*.4).toFixed(3)}deg) rotateY(${(smx*7+fl).toFixed(3)}deg) translate3d(${(-cur*XS).toFixed(2)}px,0,${(cur*ZS).toFixed(2)}px)`;
  hangs().forEach(h=>{const d=+h.dataset.i-cur;let o=1;if(d<-.35)o=Math.max(0,1+(d+.35)*2.6);else if(d>6)o=Math.max(0,1-(d-6)/3);h.style.opacity=o.toFixed(3);h.style.visibility=o<.02?'hidden':'visible';h.style.pointerEvents=o<.3?'none':'auto'});
  const ci=Math.max(0,Math.min(N-1,Math.round(cur)));
  if(ci!==frame.last){frame.last=ci;const p=P[ci],c=CO[p.co];
    $('#hn').innerHTML=`${pad(ci+1)}<small>/${N}</small>`;$('#ht').textContent=p.t;$('#hm').innerHTML=`<b style="background:${c.ac}"></b>${c.s} · ${c.cat}`;
    $$('#ticks button').forEach((b,k)=>b.classList.toggle('on',k===ci));
    $$('#hchips button').forEach(b=>b.classList.toggle('on',b.dataset.f===p.co))}
  if(view==='about')$$('.wa').forEach(a=>{const dp=+a.dataset.depth;a.style.transform=`translate(${smx*dp}px,${smy*dp}px)`});
  requestAnimationFrame(frame);
}
const clamp=v=>Math.max(0,Math.min(N-1,v));
addEventListener('pointermove',e=>{mx=e.clientX/innerWidth-.5;my=e.clientY/innerHeight-.5});
const st=$('#stage');let dn=null,moved=0,snapT;
st.addEventListener('wheel',e=>{e.preventDefault();target=clamp(target+e.deltaY/520);hide();clearTimeout(snapT);snapT=setTimeout(()=>target=Math.round(target),260)},{passive:false});
st.addEventListener('pointerdown',e=>{dn={x:e.clientX,t:target};moved=0;st.setPointerCapture(e.pointerId);st.classList.add('drag')});
st.addEventListener('pointermove',e=>{if(!dn)return;const dx=e.clientX-dn.x;moved=Math.max(moved,Math.abs(dx));target=clamp(dn.t-dx/220)});
st.addEventListener('pointerup',e=>{st.classList.remove('drag');
  if(moved<6){const el=document.elementFromPoint(e.clientX,e.clientY);
    const f=el&&el.closest('[data-open]');if(f){const i=+f.dataset.open;Math.abs(i-cur)<.5?openP(i):target=i}}
  else target=Math.round(target);dn=null;hide()});
addEventListener('keydown',e=>{
  if(view==='hang'){if(e.key==='ArrowRight'||e.key==='ArrowDown'){target=clamp(Math.round(target)+1);hide()}if(e.key==='ArrowLeft'||e.key==='ArrowUp'){target=clamp(Math.round(target)-1);hide()}if(e.key==='Enter')openP(Math.round(cur))}
  if(e.key==='Escape'&&view==='plate')go(lastView);
});
let hid=0;function hide(){if(!hid){hid=1;$('#hint').style.opacity=0}}

/* ---------- katalog ---------- */
function filterRows(f){$$('#kchips button').forEach(b=>b.classList.toggle('on',b.dataset.f===f));$$('#rows tr').forEach(tr=>tr.classList.toggle('hide',f!=='all'&&tr.dataset.co!==f))}
$('#kchips').addEventListener('click',e=>{const b=e.target.closest('button');if(b)filterRows(b.dataset.f)});
const pk=$('#peek');
$('#rows').addEventListener('mousemove',e=>{const tr=e.target.closest('tr');if(!tr)return;pk.querySelector('img').src=TH[+tr.dataset.i];pk.classList.add('on');pk.style.left=Math.min(e.clientX+150,innerWidth-120)+'px';pk.style.top=e.clientY+'px'});
$('#rows').addEventListener('mouseleave',()=>pk.classList.remove('on'));
$('#rows').addEventListener('click',e=>{const tr=e.target.closest('tr');if(tr){pk.classList.remove('on');openP(+tr.dataset.i)}});

/* ---------- gallery ---------- */
let TW=0,TH_=0;
const lt=$('#lt'),tb=$('#table');let tx=0,ty=0,sc=.6,vx=0,vy=0,ld=null,lm=0;
function applyT(){tb.style.transform=`translate(${tx}px,${ty}px) scale(${sc})`;$('#zl').textContent=Math.round(sc*100)+'%'}
function centerT(){tx=(innerWidth-TW*sc)/2;ty=(innerHeight-TH_*sc)/2;applyT()}
function zoomAt(f,cx=innerWidth/2,cy=innerHeight/2){const ns=Math.max(.3,Math.min(1.6,sc*f));tx=cx-(cx-tx)*ns/sc;ty=cy-(cy-ty)*ns/sc;sc=ns;applyT()}
lt.addEventListener('wheel',e=>{e.preventDefault();zoomAt(e.deltaY<0?1.1:.9,e.clientX,e.clientY)},{passive:false});
lt.addEventListener('pointerdown',e=>{ld={lx:e.clientX,ly:e.clientY};lm=0;vx=vy=0;lt.setPointerCapture(e.pointerId);lt.classList.add('drag')});
lt.addEventListener('pointermove',e=>{if(!ld)return;const dx=e.clientX-ld.lx,dy=e.clientY-ld.ly;tx+=dx;ty+=dy;vx=dx;vy=dy;ld.lx=e.clientX;ld.ly=e.clientY;lm+=Math.abs(dx)+Math.abs(dy);applyT()});
lt.addEventListener('pointerup',e=>{lt.classList.remove('drag');
  if(lm<6){const el=document.elementFromPoint(e.clientX,e.clientY),s=el&&el.closest('.slide');if(s)openP(+s.dataset.i)}
  ld=null;(function inertia(){if(ld||Math.abs(vx)+Math.abs(vy)<.3)return;tx+=vx;ty+=vy;vx*=.93;vy*=.93;applyT();requestAnimationFrame(inertia)})()});
$('#zi').onclick=()=>zoomAt(1.2);$('#zo').onclick=()=>zoomAt(1/1.2);
$('#gchips').addEventListener('click',e=>{const b=e.target.closest('button');if(!b)return;$$('#gchips button').forEach(x=>x.classList.toggle('on',x===b));const f=b.dataset.f;$$('.slide').forEach(s=>s.classList.toggle('dim',f!=='all'&&s.dataset.co!==f))});

/* ---------- detail produk ---------- */
let lastView='hang',open=0;const VAR={};
function openP(i){
  if(view!=='plate')lastView=view;open=i;const p=P[i],c=CO[p.co];
  if(!VAR[i])VAR[i]=[IMG[i],draw(p,480,600,1),draw(p,480,600,2)];
  $('#pimg').src=VAR[i][0];
  $('#pthumbs').innerHTML=VAR[i].map((s,k)=>`<img src="${s}" class="${k?'':'on'}" alt="Tampilan ${k+1}">`).join('');
  $('#pno').textContent=`No.${pad(i+1)} / ${N} · ${c.cat}`;
  $('#pt').textContent=p.t;$('#pl').textContent=c.n;$('#pd').textContent=p.d;
  $('#pdl').innerHTML=`<dt>Perusahaan</dt><dd>${c.n}</dd><dt>Bidang</dt><dd>${c.cat}</dd><dt>Teknologi</dt><dd>${p.tech}</dd><dt>Aplikasi</dt><dd>${p.use}</dd><dt>Berdiri</dt><dd>${c.y}</dd><dt>Telepon</dt><dd>${c.tel}</dd><dt>Lokasi</dt><dd>${c.addr}</dd>`;
  $('#pwa').href=waLink(p.co,`Halo ${c.s}, saya tertarik dengan produk ${p.t}. Boleh minta info harga dan minimum order?`);
  $('#psite').href=c.url;$('#psite').textContent=`Kunjungi situs ${c.s} ↗`;
  go('plate');
}
$('#pthumbs').addEventListener('click',e=>{const t=e.target.closest('img');if(!t)return;$$('#pthumbs img').forEach(x=>x.classList.toggle('on',x===t));const m=$('#pimg');m.animate([{opacity:.2},{opacity:1}],{duration:400});m.src=t.src});
$('#back').onclick=()=>go(lastView);
$('#pnext').onclick=()=>{const n=(open+1)%N;target=n;openP(n)};
$('#penq').onclick=()=>{const p=P[open];$('#selco').value=p.co;fillProducts(p.co,p.i);$('#ctbg').style.backgroundImage=`url(${IMG[p.i]})`;$('#form').classList.remove('done');go('contact')};

/* ---------- kontak ---------- */
$('#form').addEventListener('submit',e=>{e.preventDefault();
  const k=$('#selco').value,c=CO[k],pv=$('#selp').value,prod=pv===''?'produk/layanan Anda':P[+pv].t;
  const inst=$('#fi').value.trim(),need=$('#fm').value.trim();
  const msg=`Halo ${c.s}, saya ${$('#fn').value.trim()}${inst?' dari '+inst:''}. Saya tertarik dengan ${prod}.${need?'\n\nKebutuhan: '+need:''}\n\nMohon info harga dan ketersediaannya. Terima kasih.`;
  window.open(waLink(k,msg),'_blank','noopener');e.currentTarget.classList.add('done')});
$('#again').onclick=()=>$('#form').classList.remove('done');

/* ---------- boot ---------- */
setTimeout(()=>{build();layout();centerT();pill();frame();stagger($('#v-hang'));
  const L=$('#load');L.style.opacity=0;setTimeout(()=>L.remove(),800)},30);
