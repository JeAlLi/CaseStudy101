<?php
header('Content-Type: application/javascript; charset=utf-8');
?>
function _B(start, end) {
    var a = [];
    for (var i = start; i <= end; i++) a.push("Barangay " + i);
    return a;
}

window.PH_LOCATIONS = {
  // ==========================================
  // METRO MANILA (NCR)
  // ==========================================
  "Manila": {
    "Tondo":       _B(1, 267),
    "Sta. Cruz":   _B(268, 372),
    "Sampaloc":    _B(373, 585),
    "San Miguel":  _B(586, 654),
    "Binondo":     _B(655, 675),
    "San Nicolas": _B(676, 702),
    "Quiapo":      _B(703, 711),
    "Sta. Mesa":   _B(712, 732),
    "Pandacan":    _B(733, 745),
    "Paco":        _B(746, 772),
    "Malate":      _B(773, 786),
    "Ermita":      _B(787, 801),
    "Intramuros":  _B(802, 812),
    "Port Area":   _B(813, 835),
    "San Andres":  _B(836, 870)
  },
  "Quezon City": {
    "District 1": ["Alicia", "Bagong Pag-asa", "Bahay Toro", "Balingasa", "Bungad", "Damar", "Damayan", "Del Monte", "Katipunan", "Lourdes", "Maharlika", "Manresa", "Mariblo", "Masambong", "N.S. Amoranto", "Nayong Kanluran", "Paang Bundok", "Pag-ibig sa Nayon", "Paltok", "Paraiso", "Phil-am", "Project 6", "Ramon Magsaysay", "Saint Peter", "Salvacion", "San Antonio", "San Bartolome", "San Isidro Labrador", "San Jose", "Santa Cruz", "Santa Teresita", "Sto. Cristo", "Sto. Domingo", "Sto. Niño", "Talayan", "Vasra", "Veterans Village", "West Triangle"],
    "District 2": ["Bagong Silangan", "Batasan Hills", "Commonwealth", "Holy Spirit", "Payatas"],
    "District 3": ["Amihan", "Bagumbayan", "Bagumbuhay", "Bayanihan", "Blue Ridge A", "Blue Ridge B", "Camp Aguinaldo", "Claro", "Dioquino Zobel", "Duyan-duyan", "E. Rodriguez", "East Kamias", "Escopa I", "Escopa II", "Escopa III", "Escopa IV", "Libis", "Loyola Heights", "Mangga", "Marilag", "Masagana", "Matandang Balara", "Milagrosa", "Pansol", "Quisumbing", "Tagumpay", "Villa Maria Clara", "West Kamias", "White Plains"],
    "District 4": ["Bagong Lipunan ng Crame", "Botocan", "Central", "Damayang Lagi", "Don Manuel", "Dona Aurora", "Dona Imelda", "Dona Josefa", "Horseshoe", "Immaculate Concepcion", "Kalusugan", "Kamuning", "Katipunan", "Kaunlaran", "Kristong Hari", "Krus na Ligas", "Laging Handa", "Malaya", "Mariana", "Obrero", "Old Capitol Site", "Paligsahan", "Pinagkaisahan", "Pinyahan", "Roxas", "Sacred Heart", "San Isidro", "San Martin de Porres", "San Roque", "Santol", "Sikatuna Village", "South Triangle", "Sto. Niño", "Tatalon", "Teachers Village East", "Teachers Village West", "U.P. Campus", "U.P. Village", "Valencia"],
    "District 5": ["Bagbag", "Capri", "Fairview", "Greater Lagro", "Gulod", "Kaligayahan", "Nagkaisang Nayon", "Novaliches Proper", "Pasong Putik Proper", "San Agustin", "San Bartolome", "Santa Lucia", "Santa Monica"],
    "District 6": ["Apolonio Samson", "Baesa", "Balong Bato", "Culiat", "New Era", "Pasong Tamo", "Sangandaan", "Tandang Sora", "Unang Sigaw"]
  },
  "Caloocan": {
    "District 1": _B(1, 4).concat(_B(77, 85), _B(132, 136)),
    "District 2": _B(5, 76).concat(_B(86, 131)),
    "District 3": _B(178, 188)
  },
  "Pasay": {
    "District 1": _B(1, 100),
    "District 2": _B(101, 201)
  },
  "Makati": {
    "District 1": ["Bangkal","Bel-Air","Carmona","Forbes Park","Kasilawan","La Paz","Magallanes","Olympia","Palanan","Pio del Pilar","Poblacion","San Antonio","San Lorenzo","Singkamas","Sta. Cruz","Tejeros","Urdaneta","Valenzuela"],
    "District 2": ["Cembo","Comembo","East Rembo","Guadalupe Nuevo","Guadalupe Viejo","Pembo","Pitogo","Post Proper Northside","Post Proper Southside","Rizal","South Cembo","West Rembo"]
  },
  "Valenzuela": {
    "District 1": ["Arkong Bato","Balangkas","Bignay","Bisig","Canumay East","Canumay West","Coloong","Dalandanan","Isla","Lawang Bato","Lingunan","Mabolo","Malanday","Palasan","Pariancillo Villa","Pasolo","Poblacion","Polo","Punturin","Rincon","Tagalag","Wawang Pulo"],
    "District 2": ["Bagbaguin","Gen. T. de Leon","Karuhatan","Mapulang Lupa","Marulas","Maysan","Parada","Paso de Blas","Ugong"]
  },
  "Taguig": ["Bagumbayan","Bambang","Calzada","Hagonoy","Ibayo-Tipas","Ligid-Tipas","Lower Bicutan","New Lower Bicutan","Napindan","Palingon","San Miguel","Santa Ana","Signal Village","Tuktukan","Upper Bicutan","Ususan","Wawa","Western Bicutan"],
  "Pasig": ["Bagong Ilog","Bagong Katipunan","Bambang","Buting","Caniogan","Dela Paz","Kalawaan","Kapasigan","Kapitolyo","Malinao","Manggahan","Maybunga","Oranbo","Palatiw","Pinagbuhatan","Pineda","Rosario","Sagad","San Antonio","San Joaquin","San Jose","San Miguel","San Nicolas","Santa Cruz","Santa Lucia","Santa Rosa","Santo Tomas","Santolan","Sumilang","Ugong"],
  "Parañaque": ["Baclaran","BF Homes","Don Bosco","Don Galo","La Huerta","Marcelo Green","Merville","Moonwalk","San Antonio","San Dionisio","San Isidro","San Martin de Porres","Sto. Niño","Sun Valley","Tambo","Vitalez"],
  "Las Piñas": ["Almanza Uno","Almanza Dos","BF International","Daniel Fajardo","Elias Aldana","Ilaya","Manuyo Uno","Manuyo Dos","Pamplona Uno","Pamplona Dos","Pamplona Tres","Pilar","Pulang Lupa Uno","Pulang Lupa Dos","Talon Uno","Talon Dos","Talon Tres","Talon Kuatro","Talon Singko","Zapote"],
  "Muntinlupa": ["Alabang","Ayala Alabang","Bayanan","Buli","Cupang","Poblacion","Putatan","Sucat","Tunasan"],
  "Mandaluyong": ["Addition Hills","Bagong Silang","Barangka Drive","Barangka Ibaba","Barangka Ilaya","Barangka Itaas","Buayang Bato","Burol","Daang Bakal","Hagdang Bato Itaas","Hagdang Bato Libis","Harapin ang Bukas","Highway Hills","Hulo","Mabini-J. Rizal","Malamig","Mauway","Namayan","New Zañiga","Old Zañiga","Pag-asa","Plainview","Pleasant Hills","Poblacion","San Jose","Vergara","Wack-Wack Greenhills"],
  "San Juan": ["Addition Hills","Balong-Bato","Batis","Corazon de Jesus","Ermitaño","Greenhills","Isabelita","Kabayanan","Little Baguio","Maytunas","Onse","Pasadeña","Pedro Cruz","Progreso","Rivera","Salapan","San Perfecto","Sta. Lucia","Tibagan"],
  "Marikina": ["Barangka","Calumpang","Concepcion Uno","Concepcion Dos","Fortune","Industrial Valley","Jesus dela Peña","Kalumpang","Malanday","Marikina Heights","Nangka","Parang","San Roque","Santa Elena","Santo Niño","Tañong","Tumana"],
  "Malabon": ["Acacia","Baritan","Bayan-bayanan","Catmon","Concepcion","Dampalit","Flores","Hulong Duhat","Ibaba","Longos","Maysilo","Muzon","Niugan","Panghulo","Potrero","San Agustin","San Jose","Tañong","Tinajeros","Tonsuya","Tugatog"],
  "Navotas": ["Bagumbayan North","Bagumbayan South","Bangkulasi","Daanghari","Navotas East","Navotas West","NBBS Dagat-dagatan","NBBS Kaunlaran","NBBS Proper","North Bay Boulevard North","North Bay Boulevard South","San Jose","San Rafael Village","San Roque","Sipac-Almacen","Tangos North","Tangos South","Tanza 1","Tanza 2"],
  "Pateros": ["Aguho","Magtanggol","Martires del 96","Poblacion","San Pedro","San Roque","Santa Ana"],

  // ==========================================
  // CAVITE
  // ==========================================
  "Bacoor": ["Aniban I","Aniban II","Aniban III","Aniban IV","Aniban V","Banalo","Bayanan","Campo Santo","Daang Bukid","Digman","Dulong Bayan","Habay I","Habay II","Kaingin","Ligas I","Ligas II","Ligas III","Mabolo I","Mabolo II","Mabolo III","Maliksi I","Maliksi II","Maliksi III","Mambog I","Mambog II","Mambog III","Mambog IV","Mambog V","Molino I","Molino II","Molino III","Molino IV","Molino V","Molino VI","Molino VII","Niog I","Niog II","Niog III","Panapaan I","Panapaan II","Panapaan III","Panapaan IV","Panapaan V","Panapaan VI","Panapaan VII","Panapaan VIII","P.F. Espiritu I","P.F. Espiritu II","P.F. Espiritu III","P.F. Espiritu IV","P.F. Espiritu V","P.F. Espiritu VI","P.F. Espiritu VII","P.F. Espiritu VIII","Queens Row Central","Queens Row East","Queens Row West","Real I","Real II","Salinas I","Salinas II","Salinas III","Salinas IV","San Nicolas I","San Nicolas II","San Nicolas III","Sineguelasan","Tabing Dagat","Talaba I","Talaba II","Talaba III","Talaba IV","Talaba V","Talaba VI","Talaba VII","Zapote I","Zapote II","Zapote III","Zapote IV","Zapote V"],
  "Imus": ["Alapan I-A","Alapan I-B","Alapan I-C","Alapan II-A","Alapan II-B","Anabu I-A","Anabu I-B","Anabu I-C","Anabu I-D","Anabu I-E","Anabu I-F","Anabu I-G","Anabu II-A","Anabu II-B","Anabu II-C","Anabu II-D","Anabu II-E","Anabu II-F","Bagong Silang","Bayan Luma I","Bayan Luma II","Bayan Luma III","Bayan Luma IV","Bayan Luma V","Bayan Luma VI","Bayan Luma VII","Bayan Luma VIII","Bayan Luma IX","Bucandala I","Bucandala II","Bucandala III","Bucandala IV","Bucandala V","Buhay na Tubig","Caridad","Carsadang Bago I","Carsadang Bago II","Magdalo","Maharlika","Malagasang I-A","Malagasang I-B","Malagasang I-C","Malagasang I-D","Malagasang I-E","Malagasang I-F","Malagasang I-G","Malagasang II-A","Malagasang II-B","Malagasang II-C","Malagasang II-D","Malagasang II-E","Malagasang II-F","Malagasang II-G","Mariano Espeleta I","Mariano Espeleta II","Mariano Espeleta III","Medicion I-A","Medicion I-B","Medicion I-C","Medicion I-D","Medicion II-A","Medicion II-B","Medicion II-C","Medicion II-D","Medicion II-E","Medicion II-F","Pag-asa I","Pag-asa II","Pag-asa III","Palico I","Palico II","Palico III","Palico IV","Pasong Buaya I","Pasong Buaya II","Pinagbuklod","Poblacion I-A","Poblacion I-B","Poblacion I-C","Poblacion II-A","Poblacion II-B","Poblacion III-A","Poblacion III-B","Poblacion IV-A","Poblacion IV-B","Poblacion IV-C","Poblacion IV-D","Tanzang Luma I","Tanzang Luma II","Tanzang Luma III","Tanzang Luma IV","Tanzang Luma V","Tanzang Luma VI","Toclong I-A","Toclong I-B","Toclong I-C","Toclong II-A","Toclong II-B"],
  "Dasmariñas": ["Burol I","Burol II","Burol III","Datu Esmael","Emmanuel Bergado I","Emmanuel Bergado II","Fatima I","Fatima II","Fatima III","H-2","Langkaan I","Langkaan II","Luzviminda I","Luzviminda II","Paliparan I","Paliparan II","Paliparan III","Sabang","Salawag","Salitran I","Salitran II","Salitran III","Salitran IV","Sampaloc I","Sampaloc II","Sampaloc III","Sampaloc IV","Sampaloc V","San Agustin I","San Agustin II","San Agustin III","San Andres I","San Andres II","San Jose","San Manuel I","San Manuel II","San Mateo","San Nicolas I","San Nicolas II","San Roque","Santa Cristina I","Santa Cristina II","Santa Cruz I","Santa Cruz II","Santa Fe","Santa Lucia","Santa Maria","Santo Cristo","Santo Niño I","Santo Niño II","Zone I","Zone I-A","Zone II","Zone III","Zone IV"],
  "General Trias": ["Alingaro","Arnaldo","Bacao I","Bacao II","Bagumbayan","Buenavista I","Buenavista II","Buenavista III","Corregidor","Daan Bucal","Dulong Bayan","Goues","Javalera","Manggahan","Munting Mapino","Navarro","Panungyanan","Pasong Camachile I","Pasong Camachile II","Pasong Kawayan I","Pasong Kawayan II","Pinagtipunan","Prinza","Sampaloc I","Sampaloc II","Sampaloc III","Sampaloc IV","Sampaloc V","San Francisco","San Gabriel","San Juan I","San Juan II","Santa Clara","Santiago","Tapia","Tejero","Vibora","Ylang-Ylang"],

  // ==========================================
  // LAGUNA
  // ==========================================
  "Calamba": ["Bagong Kalsada","Banadero","Banlic","Barandal","Batino","Bubuyan","Bucal","Bunggo","Burol","Camaligan","Canlubang","Halang","Hornalan","Kay-Anlog","La Mesa","Lecheria","Lingga","Looc","Mabato","Majada Labas","Makiling","Mapagong","Masili","Maunong","Mayapa","Milagrosa","Paciano Rizal","Palingon","Palo-Alto","Pansol","Parian","Barangay 1","Barangay 2","Barangay 3","Barangay 4","Barangay 5","Barangay 6","Barangay 7","Prinza","Punta","Real","Saimsim","Sampiruhan","San Cristobal","San Jose","San Juan","Sirang Lupa","Sucol","Turbina","Ulango","Uwisan"],
  "Santa Rosa": ["Aplaya","Balibago","Caingin","Dila","Dita","Don Jose","Ibaba","Kanluran","Labas","Macabling","Malitlit","Malusak","Market Area","Pooc","Pulong Santa Cruz","Santo Domingo","Sinalhan","Tagapo"],
  "Biñan": ["Biñan","Bungahan","Canlalay","Casile","De La Paz","Ganado","Langkiwa","Loma","Malaban","Malamig","Mampalasan","Platero","Poblacion","San Antonio","San Francisco","San Jose","San Vicente","Santo Domingo","Santo Niño","Soro-Soro","Timbao","Tubigan","Zapote"],
  "San Pedro": ["Bagong Silang","Calendola","Chrysanthemum","Cuyab","Estrella","Fatima","G.S.I.S.","Landayan","Langgam","Laram","Magsaysay","Maharlika","Narra","Nueva","Pacita 1","Pacita 2","Poblacion","Riverside","Rosario","Sampaguita Village","San Antonio","San Lorenzo Ruiz","San Roque","San Vicente","Santo Niño","United Bayanihan","United Better Living"],

  // ==========================================
  // RIZAL
  // ==========================================
  "Antipolo": ["Bagong Nayon","Beverly Hills","Calawis","Cupang","Dalig","Dela Paz","Inarawan","Mambugan","Mayamot","Muntingdilaw","San Isidro","San Jose","San Juan","San Luis","San Roque","Santa Cruz","Santo Niño"],
  "Cainta": ["San Andres","San Isidro","San Juan","San Roque","Sto. Domingo","Sto. Niño","Sto. Rosario"],
  "Taytay": ["Dolores","Muzon","San Isidro","San Juan","Sta. Ana"],
  "Angono": ["Bagumbayan","Kalayaan","Poblacion Ibaba","Poblacion Itaas","San Isidro","San Pedro","San Roque","San Vicente","Santo Niño"],

  // ==========================================
  // BULACAN
  // ==========================================
  "Malolos": ["Anilao","Atlag","Babatnin","Bagna","Bagong Bayan","Balayong","Balite","Bangkal","Barangay 1","Barangay 2","Barangay 3","Barangay 4","Barihan","Bulihan","Bungahan","Caingin","Calero","Caliligawan","Canalate","Caniogan","Catmon","Cofradia","Dakila","Guinhawa","Ligas","Liyang","Longos","Look 1st","Look 2nd","Lugam","Mabolo","Mambog","Masile","Matimbo","Mojon","Namayan","Niugan","Pamarawan","Panus","Pinasahan","Potrer","Pulong Duhat","San Agustin","San Bartolome","San Gabriel","San Juan","San Nicolas","San Pablo","San Vicente","Santiago","Santissima Trinidad","Santo Cristo","Santo Niño","Santo Rosario","Sumapang Bata","Sumapang Matanda","Taal","Tikay"],
  "Meycauayan": ["Bagbaguin","Bahay Pare","Bancal","Banga","Bayugo","Caingin","Calvario","Camalig","Hulo","Ibaba","Iba","Lawa","Libtong","Longos","Malhacan","Pajo","Pandayan","Pantoc","Perez","Poblacion","Saluysoy","St. Francis","Tugatog","Ubihan","Zamora"],
  "Marilao": ["Abangan Norte","Abangan Sur","Ibayo","Lambakin","Lias","Loma de Gato","Nagbalon","Patubig","Poblacion I","Poblacion II","Prenza I","Prenza II","Santa Rosa I","Santa Rosa II","Saog","Tabing Ilog"],

  // ==========================================
  // PAMPANGA
  // ==========================================
  "San Fernando": ["Alasas","Baliti","Bulaon","Calulut","Del Carmen","Del Pilar","Del Rosario","Dolores","Juliana","Lara","Lourdes","Magliman","Maimpis","Malino","Malpitic","Pandaras","Panipuan","Poblacion","Quebiawan","Saguin","San Agustin","San Felipe","San Isidro","San Jose","San Juan","San Nicolas","San Pedro","Santa Lucia","Santa Teresita","Santo Niño","Santo Rosario","Sindalan","Telabastagan"],
  "Angeles": ["Agapito del Rosario","Anunas","Balibago","Capaya","Claro M. Recto","Cuayan","Cutcut","Cutud","Lourdes North West","Lourdes Sur","Lourdes Sur East","Malabañas","Margot","Mining","Pampang","Pandan","Pulung Cacutud","Pulung Maragul","Pulungbulu","Salapungan","San Jose","San Nicolas","Santa Teresita","Santa Trinidad","Santo Cristo","Santo Domingo","Santo Rosario","Sapalibutad","Sapangbato","Tabun","Virgen Delos Remedios"],

  // ==========================================
  // VISAYAS
  // ==========================================
  "Cebu City": {
    "North": ["Adlaon","Agsungot","Apas","Babag","Bacayan","Banilad","Binaliw","Budla-an","Busay","Cambinocot","Capitol Site","Carreta","Cogon Ramos","Day-as","Ermita","Guba","Hipodromo","Kalubihan","Kamagayan","Kamputhaw","Kasambagan","Kinasang-an Pardo","Lahug","Lorega","Lusaran","Luz","Mabini","Mabolo","Malubog","Mambaling","Pahina Central","Pahina San Nicolas","Pamutan","Pardo","Pari-an","Paril","Pasil","Pit-os","Pulangbato","Pung-ol Sibugay","Punta Princesa","Quiot Pardo","Sambag I","Sambag II","San Antonio","San Jose","San Nicolas Central","San Roque","Santa Cruz","Santo Niño","Sapangdaku","Sawang Calero","Sinsin","Sirao","Sudlon I","Sudlon II","T. Padilla","Tabunan","Tagbao","Talamban","Taptap","Tejero","Tinago","To-ong Pardo","Zapatera"],
    "South": ["Basak Pardo","Basak San Nicolas","Bonbon","Bulacao","Buot-Taup Pardo","Calamba","Cogon Pardo","Duljo","Inayawan","Kalunasan","Kinasang-an Pardo","Labangon","Mambaling","Pahina San Nicolas","Pamutan","Pardo","Pasil","Poblacion Pardo","Punta Princesa","Quiot Pardo","San Nicolas Proper","Sapangdaku","Sudlon I","Sudlon II","Tisa","To-ong Pardo"]
  },
  "Iloilo City": {
    "Jaro": ["Benedicto","Bito-on","Cuartero","Cubay","Democracia","Dungon A","Dungon B","Lanit","Lapaz","Libertad","Montinola","MV Hechanova","Our Lady of Lourdes","San Isidro","San Jose","San Pedro","San Roque","Seminario","Simon Ledesma","Tacas","Tagbac","Tabuc Suba"],
    "La Paz": ["Aguinaldo","Bakhaw","Baldoza","Bolilao","Buntatala","Caingin","Calahunan","Calaparan","Dungon A/B","Gustilo","Hibao-an Norte","Hibao-an Sur","Jalandoni","Kauswagan","Lopez Jaena","Luna","Magsaysay","Pale","San Isidro","San Nicolas","Tanza Baybay","Ticud"],
    "Mandurriao": ["Airport","Bakhaw","Bolilao","Buhang","Dungon","Guinhalaran","Ledesco","Lopez Jaena","Mabini","Magsaysay","Mandurriao","Pali-Benedicto","Q. Abeto","San Rafael","Sta. Rosa","Tabucan","Taft North","Ubaldo Ledesma"],
    "Molo": ["Avanceña","Bonifacio","Calumpang","Compania","East Baluarte","Fajardo","Habog-Habog","Infante","Kasing-kasing","Katilingban","Locsin","Marcelo","North Baluarte","North San Jose","Poblacion","Rizal","San Antonio","San Juan","San Pedro","South Baluarte","South San Jose","Taal","Tap-oc","West Habog-Habog","West Timawa","West San Jose"],
    "Arevalo": ["Bonifacio","Calaparan","Dulonan","Hibao-an","Ingore","Lopez Jaena","Mohon","Quezon","San Jose","Sto. Domingo","Sto. Niño Norte","Sto. Niño Sur","Sta. Cruz","Sta. Filomena","Sta. Maria"],
    "City Proper": ["Arsenal-Aduana","Baybay Tanza","Bonifacio","Bureau of Posts","Dungon","Edganzon","General Hughes","Hipodromo","Inday","Legaspi dela Rama","Libertad","Luna","Mabini","Muelle Loney","Ortiz","Plaza Libertad","Rizal","San Agustin","San Jose","San Pedro","Sta. Cruz","Sto. Rosario","Tanza Baybay","Veterans Village","Zamora"]
  },

  // ==========================================
  // MINDANAO
  // ==========================================
  "Davao City": {
    "Poblacion": ["1-A","2-A","3-A","4-A","5-A","6-A","7-A","8-A","9-A","10-A","11-B","12-B","13-B","14-B","15-B","16-B","17-B","18-B","19-B","20-B","21-C","22-C","23-C","24-C","25-C","26-C","27-C","28-C","29-C","30-C","31-D","32-D","33-D","34-D","35-D","36-D","37-D","38-D","39-D","40-D"],
    "Talomo": ["Bago Aplaya","Bago Gallera","Baliok","Bucana","Catalunan Grande","Catalunan Pequeño","Dumoy","Langub","Ma-a","Magtuod","Matina Aplaya","Matina Crossing","Matina Pangi","Talomo Proper","Talomo River"],
    "Agdao": ["Agdao Proper","Centro","Gov. Paciano Bangoy","Gov. Vicente Duterte","Kap. Tomas Monteverde Sr.","Lapu-Lapu","Leon Garcia Sr.","Manuel Guianga","Rafael Castillo","San Antonio","Ubalde","Wilfredo Aquino"],
    "Buhangin": ["Biao Escuela","Biao Guianga","Biao Joaquin","Buhangin Proper","Cabantian","Cadalian","Callawa","Carmen","Communal","Fatima","Gumalang","Indangan","Lamanan","Lizada","Los Amigos","Malabog","Mandug","Pampanga","Sirib","Tagakpan","Talandang","Tugbok","Waan","Wangan"]
  },
  "Cagayan de Oro": ["Agusan","Balubal","Bayabas","Bonbon","Bugo","Bulua","Camaman-an","Canito-an","Carmen","Consolacion","Cugman","F.S. Catanico","Gusa","Indahag","Iponan","Kauswagan","Lapasan","Lumbia","Macabalan","Macasandig","Nazareth","Patag","Pigsag-an","Puerto","Puntod","San Simon","Tablon","Taglimao","Tagpangi","Tignapoloan","Tuburan","Tumpagon"],
  "Zamboanga City": ["Arena Blanco","Ayala","Baliwasan","Boalan","Bolong","Buenavista","Bunguiao","Busay","Cabaluay","Cabatangan","Cacao","Calabasa","Calarian","Camino Nuevo","Campo Islam","Canelar","Cawit","Culianan","Curuan","Dita","Divisoria","Dulian","Dumalinao","Guiwan","Kasanyangan","La Paz","Labuan","Lamisahan","Landang Gua","Landang Laum","Lanzones","Lapakan","Limpapa","Lunzuran","Maasin","Malagutay","Mampang","Manalipa","Manicahan","Mariki","Mercedes","Muti","Pamucutan","Pangapuyan","Panubigan","Pasilmanta","Pasonanca","Patalon","Recodo","Rio Hondo","San Jose Cawa-Cawa","San Jose Gusu","San Roque","Santa Barbara","Santa Catalina","Santa Maria","Santo Niño","Sibulao","Sinunuc","Talisayan","Talabaan","Talon-Talon","Taluksangay","Tetuan","Tictapul","Tigbalabag","Tigtabon","Tolosa","Tugbungan","Tulungatung","Tumaga","Tumalutab","Vitali","Zambowood"],
  "General Santos": ["Apopong","Baluan","Batomelong","Buayan","Bula","Calumpang","City Heights","Conel","Dadiangas East","Dadiangas North","Dadiangas South","Dadiangas West","Fatima","Katangawan","Labangal","Lagao","Ligaya","Mabuhay","Makaring","Malakas","Malungon","Olympog","San Isidro","San Jose","Siguel","Sinawal","Tambler","Tinagacan","Upper Labay"]
};

window.isCityWithDistricts = function(city) {
    var v = window.PH_LOCATIONS[city];
    return v && typeof v === 'object' && !Array.isArray(v);
};

window.getDistricts = function(city) {
    if (!window.isCityWithDistricts(city)) return [];
    return Object.keys(window.PH_LOCATIONS[city]);
};

window.getBarangays = function(city, district) {
    var v = window.PH_LOCATIONS[city];
    if (!v) return [];
    if (Array.isArray(v)) return v;
    if (district && v[district]) return v[district];
    return [];
};

window.searchCities = function(q) {
    q = (q || '').toLowerCase().trim();
    if (!q) return [];
    return Object.keys(window.PH_LOCATIONS).filter(function(c) {
        return c.toLowerCase().indexOf(q) !== -1;
    }).sort();
};