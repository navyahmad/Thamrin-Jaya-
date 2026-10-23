/* Data dari thamrinjaya.com (disesuaikan dengan rute proyek Laravel) */
const U=window.TJ_COMPANY_URLS||{};
const CO={
 sj:{n:'CV Sinar Jaya',s:'Sinar Jaya',cat:'Cigarette Material',y:2012,wa:'6281331555360',tel:'0813-3155-5360',addr:'Kuburan, Kemiri Sewu, Pandaan, Pasuruan',url:U.sj||'/sinar-jaya',bg:['#F0E8D2','#E2D4B0'],ac:'#A8721F',d:'Penyedia bahan baku rokok sejak 2012: kertas sigaret, filter, tipping paper, hingga kemasan pendukung.'},
 gl:{n:'PT Globalindo Thamrin Jaya',s:'Globalindo',cat:'Packaging Printing',y:2017,wa:'6287751275554',tel:'0877-5127-5554',addr:'Ledok, Kemiri Sewu, Pandaan, Pasuruan',url:U.gl||'/globalindo',bg:['#F3E7D8','#E6D3BD'],ac:'#C2412D',d:'Percetakan offset dan packaging sejak 2017, kapasitas hingga 5 juta lembar per bulan di pabrik seluas 1 hektar.'},
 hte:{n:'PT HTE Rotopack Indonesia',s:'HTE Rotopack',cat:'Packaging Printing',y:2024,wa:'6282264490519',tel:'0822-6449-0519',addr:'Ledok, Kemiri Sewu, Pandaan, Pasuruan',url:U.hte||'/hte-rotopack',bg:['#E3EAEC','#CCD8DC'],ac:'#2F6F8F',d:'Produsen kemasan fleksibel sejak 2024 dengan teknologi flexo dan rotogravure, khusus cetak kertas tipping rokok food grade.'},
 tp:{n:'Top Printing Jaya',s:'Top Printing',cat:'Packaging Printing',y:2022,wa:'6281259539186',tel:'0812-5953-9186',addr:'Jl. Raya Bakalan A No.1, Pagak, Beji, Pasuruan',url:U.tp||'/top-printing',bg:['#F4E0E2','#EAC9CF'],ac:'#C94C6E',d:'Digital printing sejak 2022 untuk kebutuhan personal maupun bisnis, termasuk cetak jersey, koper, hijab, dan stiker.'},
 mx:{n:'PT Maxtech Solution Indonesia',s:'Maxtech',cat:'Tobacco Machine',y:2023,wa:'6282265400164',tel:'0822-6540-0164',addr:'Jl. Raya Bakalan No.8, Pagak, Beji, Pasuruan',url:U.mx||'/maxtech',bg:['#E6E6E0','#D2D3CB'],ac:'#D99A1E',d:'Penyediaan, pengembangan, dan layanan teknis mesin industri rokok, termasuk perawatan berkala.'},
 mp:{n:'PT Multipack Thamrin Jaya',s:'Multipack',cat:'Carton & Converting',y:2012,wa:'6281333977793',tel:'0813-3397-7793',addr:'Jl. Raya Bakalan IV/19, Pagak, Beji, Pasuruan',url:U.mp||'/multipack',bg:['#EBDDC8','#D8C3A3'],ac:'#8A5A2B',d:'Percetakan kardus sejak 2012, segala model dan ukuran, bahan white kraft atau brown kraft.'}
};
const CATS=[['Cigarette Material',['sj']],['Packaging Printing',['gl','hte','tp']],['Tobacco Machine',['mx']],['Carton & Converting',['mp']]];
const ORDER=['sj','gl','hte','tp','mx','mp'];

const kraft='Model dan ukuran sesuai kebutuhan, bahan white kraft atau brown kraft.';
const RAW=[
 ['sj','Splicing Rokok','tape',{},'Perekat sambung khusus untuk efisiensi sambungan roll kertas di lini produksi rokok.','Perekat sambung roll','Lini produksi rokok'],
 ['sj','Filter Rokok','rods',{len:150,tip:0},'Filter rokok berkualitas tinggi untuk menyaring tar dan nikotin secara efektif.','Filter','Rokok filter'],
 ['sj','Tipping Rokok','roll',{c:'#D9A35B',pat:'cork'},'Tipping paper bermotif dan berwarna untuk mempercantik batang rokok.','Tipping paper','Pangkal batang rokok'],
 ['sj','Cigarette Rods','rods',{len:270,tip:1},'Batang rokok siap cetak untuk produksi massal dengan presisi tinggi.','Batang rokok','Produksi massal'],
 ['gl','Kemasan Makanan','box',{W:150,D:110,H:100,body:'#F7F2EA',band:'#C2412D',mark:'win'},'Berbagai jenis kemasan makanan, seperti box makanan.','Offset full colour','Makanan & minuman'],
 ['gl','Kemasan Obat','box',{W:90,D:60,H:170,body:'#FFFFFF',band:'#2E8C7A',mark:'plus'},'Box kemasan obat dari bahan karton berkualitas dengan desain presisi.','Offset, bahan karton','Farmasi'],
 ['gl','Kemasan Rokok (Etiket)','pack',{body:'#B8322A',band:'#F3E6CF'},'Kemasan rokok atau etiket dengan material berkualitas tinggi.','Offset','Industri rokok'],
 ['gl','Kemasan Kosmetik','box',{W:72,D:72,H:190,body:'#F2D4D2',band:'#C9A45C',mark:'dot'},'Box produk untuk kemasan kosmetik.','Offset','Kosmetik'],
 ['gl','Cetak Kertas & Stiker','sheet',{},'Cetak media kertas dan stiker full colour. Pesanan besar memakai mesin offset 8 warna, pesanan kecil memakai digital printing.','Offset 8 warna & digital','Brosur, label, stiker'],
 ['hte','Tipping Paper Rotogravure','roll',{c:'#E2B26A',pat:'cork gold'},'Cetak kertas tipping rokok yang menarik, berkesan premium, dan memenuhi standar food grade.','Rotogravure 10 warna','Tipping rokok'],
 ['hte','Kemasan Fleksibel','roll',{c:'#5BA4C8',pat:'flex'},'Cetak cepat hingga 10 warna dengan plat fleksibel pada plastik, kertas, dan karton.','Flexografi 10 warna','Kemasan plastik & kertas'],
 ['hte','Slitting & Rewinding','rolls3',{},'Memotong gulungan besar plastik, kertas, atau aluminium foil menjadi gulungan kecil sesuai lebar, lalu digulung ulang sesuai panjang.','Slitting, slitting Hobin, rewinding','Konversi gulungan'],
 ['hte','Die Cut Label','sticker',{c:['#2F6F8F','#E9C46A','#D9E4E8']},'Pemotongan presisi untuk membentuk stiker, label, atau kemasan sesuai pola.','Die cut RTECH','Label & stiker'],
 ['tp','Digital Print','sheet',{tp:1},'Cetak langsung dari file digital ke media tanpa plat, cocok untuk hasil cepat dan fleksibel.','Digital printing','Cetak cepat'],
 ['tp','Textile','shirt',{c:'#C94C6E'},'Cetak desain ke kain dengan DTG atau sublimasi untuk fashion dan dekorasi, termasuk jersey dan hijab.','DTG & sublimasi','Fashion & dekorasi'],
 ['tp','Stationery','cards',{},'Cetak perlengkapan seperti kartu nama dan undangan dengan presisi tinggi.','Digital printing','Kartu nama, undangan'],
 ['tp','Sticker','sticker',{c:['#C94C6E','#F2B94B','#6BB5A6']},'Stiker berbagai bentuk dan ukuran di bahan vinil atau kertas dengan daya rekat tinggi.','Vinil & kertas','Branding, label'],
 ['tp','Print Ukuran Besar','banner',{},'Banner, poster, dan backdrop dengan kualitas visual tinggi untuk promosi.','Large format','Promosi & event'],
 ['tp','Kemasan','box',{W:120,D:120,H:90,body:'#F8F1EC',band:'#C94C6E',mark:'dot'},'Cetak desain di media kemasan seperti box, sleeve, atau pouch untuk meningkatkan branding produk.','Digital printing','Branding produk'],
 ['mx','MK8 Machine','machine',{t:'maker',l:'MK8'},'Mesin pembuat rokok dengan pembentukan tembakau yang dihisap vakum dan kontrol berat rata-rata, untuk rokok filter kecepatan menengah ke atas.','Cigarette maker','Pembuatan batang rokok'],
 ['mx','MK9 Machine','machine',{t:'maker2',l:'MK9'},'Sistem pisau ganda untuk memotong batang rokok, seimbang dinamis untuk putaran lebih cepat dengan kebisingan lebih rendah.','Cigarette maker','Pembuatan batang rokok'],
 ['mx','HLP (Hinge Lid Packer)','machine',{t:'packer',l:'HLP'},'Mesin yang mengemas batangan rokok ke dalam kemasan hinge lid.','Packer','Pengemasan bungkus'],
 ['mx','Wrapper','machine',{t:'wrap',l:'WRAP'},'Membungkus bungkus rokok dengan plastik agar terlindung dari kelembapan dan kontaminasi.','Pembungkus plastik','Pengemasan bungkus'],
 ['mx','Over Wrapper','machine',{t:'wrap2',l:'O-WRAP'},'Mengemas karton rokok dengan film plastik secara lancar dan rapi.','Over-wrapping','Pengemasan karton'],
 ['mx','Boxer','machine',{t:'boxer',l:'BOXER'},'Kartonisasi otomatis: memindah produk, membuka dan mengisi kotak, lalu menyegel kedua ujung karton.','Cartoning otomatis','Pengemasan akhir'],
 ['mx','Stamper','machine',{t:'stamp',l:'STAMP'},'Menyegel bungkus rokok dengan label pita cukai, lalu dilanjutkan ke mesin pembungkus.','Stamping pita cukai','Pita cukai'],
 ['mp','Kardus B-Flute','carton',{fl:'B'},'Kardus gelombang B-flute. '+kraft,'Flexo 5 warna, die-cut','Kemasan & pengiriman'],
 ['mp','Kardus C-Flute','carton',{fl:'C'},'Kardus gelombang C-flute. '+kraft,'Flexo 5 warna, die-cut','Kemasan & pengiriman'],
 ['mp','Kardus E-Flute','carton',{fl:'E'},'Kardus gelombang E-flute, gelombang paling tipis dari keempat pilihan. '+kraft,'Flexo 5 warna, die-cut','Kemasan retail'],
 ['mp','Kardus BC-Flute','carton',{fl:'BC'},'Kardus dua lapis gelombang (gabungan B dan C). '+kraft,'Flexo 5 warna, stitching','Barang berat']
];
const P=RAW.map((a,i)=>({i,co:a[0],t:a[1],kind:a[2],o:a[3],d:a[4],tech:a[5],use:a[6],seed:2000+i*137}));
