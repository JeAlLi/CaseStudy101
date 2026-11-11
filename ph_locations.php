<?php
header('Content-Type: application/javascript; charset=utf-8');
?>
function _B(start, end) {
    var a = [];
    for (var i = start; i <= end; i++) a.push("Barangay " + i);
    return a;
}

window.PH_LOCATIONS = {
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
  "Caloocan": {
    "North": _B(1, 78),
    "South": _B(79, 188)
  },
  "Pasay": {
    "District 1": _B(1, 100),
    "District 2": _B(101, 201)
  },
  "Makati": {
    "District 1": ["Bangkal","Bel-Air","Forbes Park","Kasilawan","La Paz","Magallanes","Olympia","Palanan","Pio del Pilar","Poblacion","San Antonio","San Lorenzo","Singkamas","Sta. Cruz","Tejeros","Urdaneta","Valenzuela"],
    "District 2": ["Cembo","Comembo","East Rembo","Guadalupe Nuevo","Guadalupe Viejo","Pembo","Pitogo","Post Proper Northside","Post Proper Southside","Rizal","South Cembo","West Rembo"]
  },
  "Cebu City": {
    "North": ["Banilad","Talamban","Mabolo","Lahug","Kasambagan","Kamputhaw","Hippodromo","Capitol Site","Sto. Niño","San Roque","Pahina Central","Pahina San Nicolas","San Antonio","Carreta","Day-as","Lorega","Pari-an","T. Padilla","Zapatera"],
    "South": ["Basak San Nicolas","Basak Pardo","Bulacao","Calamba","Cogon Pardo","Duljo","Inayawan","Kinasang-an","Labangon","Mambaling","Pardo","Pasil","Poblacion Pardo","Punta Princesa","Quiot","San Nicolas","Tisa","To-ong","Tinago"]
  },
  "Iloilo City": {
    "Jaro":       ["Benedicto","Bito-on","Cuartero","Cubay","Democracia","Dungon A","Dungon B","Lanit","Lapaz","Libertad","Montinola","MV Hechanova","Our Lady of Lourdes","San Isidro","San Jose","San Pedro","San Roque","Seminario","Simon Ledesma","Tacas","Tagbac","Tabuc Suba"],
    "La Paz":     ["Aguinaldo","Bakhaw","Baldoza","Bolilao","Buntatala","Caingin","Calahunan","Calaparan","Dungon A/B","Gustilo","Hibao-an Norte","Hibao-an Sur","Jalandoni","Kauswagan","Lopez Jaena","Luna","Magsaysay","Pale","San Isidro","San Nicolas","Tanza Baybay","Ticud"],
    "Mandurriao": ["Airport","Bakhaw","Bolilao","Buhang","Dungon","Guinhalaran","Ledesco","Lopez Jaena","Mabini","Magsaysay","Mandurriao","Pali-Benedicto","Q. Abeto","San Rafael","Sta. Rosa","Tabucan","Taft North","Ubaldo Ledesma"],
    "Molo":       ["Avanceña","Bonifacio","Calumpang","Compania","East Baluarte","Fajardo","Habog-Habog","Infante","Kasing-kasing","Katilingban","Locsin","Marcelo","North Baluarte","North San Jose","Poblacion","Rizal","San Antonio","San Juan","San Pedro","South Baluarte","South San Jose","Taal","Tap-oc","West Habog-Habog","West Timawa","West San Jose"],
    "Arevalo":    ["Bonifacio","Calaparan","Dulonan","Hibao-an","Ingore","Lopez Jaena","Mohon","Quezon","San Jose","Sto. Domingo","Sto. Niño Norte","Sto. Niño Sur","Sta. Cruz","Sta. Filomena","Sta. Maria"],
    "City Proper":["Arsenal-Aduana","Baybay Tanza","Bonifacio","Bureau of Posts","Dungon","Edganzon","General Hughes","Hipodromo","Inday","Legaspi dela Rama","Libertad","Luna","Mabini","Muelle Loney","Ortiz","Plaza Libertad","Rizal","San Agustin","San Jose","San Pedro","Sta. Cruz","Sto. Rosario","Tanza Baybay","Veterans Village","Zamora"]
  },
  "Davao City": {
    "Poblacion": ["1-A","2-A","3-A","4-A","5-A","6-A","7-A","8-A","9-A","10-A","11-B","12-B","13-B","14-B","15-B","16-B","17-B","18-B","19-B","20-B","21-C","22-C","23-C","24-C","25-C","26-C","27-C","28-C","29-C","30-C","31-D","32-D","33-D","34-D","35-D","36-D","37-D","38-D","39-D","40-D"],
    "Talomo":    ["Bago Aplaya","Bago Gallera","Baliok","Bucana","Catalunan Grande","Catalunan Pequeño","Dumoy","Langub","Ma-a","Magtuod","Matina Aplaya","Matina Crossing","Matina Pangi","Talomo Proper","Talomo River"],
    "Agdao":     ["Agdao Proper","Centro","Gov. Paciano Bangoy","Gov. Vicente Duterte","Kap. Tomas Monteverde Sr.","Lapu-Lapu","Leon Garcia Sr.","Manuel Guianga","Rafael Castillo","San Antonio","Ubalde","Wilfredo Aquino"],
    "Buhangin":  ["Biao Escuela","Biao Guianga","Biao Joaquin","Buhangin Proper","Cabantian","Cadalian","Callawa","Carmen","Communal","Fatima","Gumalang","Indangan","Lamanan","Lizada","Los Amigos","Malabog","Mandug","Pampanga","Sirib","Tagakpan","Talandang","Tugbok","Waan","Wangan"]
  },

  "Quezon City": ["Bagong Silangan","Batasan Hills","Tatalon","Roxas District","Commonwealth","San Bartolome","Nagkakaisang Nayon","Sta. Lucia","Bagong Pagasa","Dona Imelda","Matandang Balara","Culiat","Apolonio Samson","Talayan","Sto. Domingo","Fairview","Novaliches","Cubao","Project 4","Project 6","Project 7","Project 8"],
  "Malabon": ["Concepcion-Ibaba","Concepcion-Baritan","Tonsuya","Tugatog","Acacia","Longos","Catmon","Hulong Duhat","Niugan","Dampalit","Muzon","Potrero"],
  "Navotas": ["Tanza Uno","Tanza Dos","San Roque","Sipac-Almacen","Navotas East","Navotas West","North Bay Boulevard"],
  "Valenzuela": ["Coloong","Wawang Pulo","Isla","Tagalag","Karuhatan","Gen. T. de Leon","Marulas","Malinta"],
  "Marikina": ["Barangka","Concepcion Uno","Concepcion Dos","Industrial Valley","Jesus dela Peña","Kalumpang","Malanday","Nangka","Parang","San Roque","Santa Elena","Santo Niño","Tañong","Tumana"],
  "Pasig": ["Bagong Ilog","Dela Paz","Pinagbuhatan","Maybunga","Santolan","Rosario","Caniogan","Manggahan"],
  "Taguig": ["Bagumbayan","Bambang","Hagonoy","Ibayo-Tipas","Ligid-Tipas","Lower Bicutan","Napindan","Signal Village"],
  "Parañaque": ["Baclaran","BF Homes","Don Bosco","La Huerta","Moonwalk","San Antonio","San Dionisio","Tambo"],
  "Las Piñas": ["Almanza Uno","Almanza Dos","BF International","Pamplona Uno","Pamplona Dos","Pamplona Tres","Talon Uno","Talon Dos","Talon Tres","Talon Kuatro","Talon Singko"],
  "Muntinlupa": ["Alabang","Ayala Alabang","Bayanan","Cupang","Poblacion","Putatan","Sucat","Tunasan"],
  "Mandaluyong": ["Maysilo","Addition Hills","Barangka Drive","Buayang Bato","Highway Hills","Mabini-JRizal","Pleasant Hills","Wack-Wack","Namayan","Hulo","Plainview","Daang Bakal","Bagong Silang"],
  "San Juan": ["Addition Hills","Corazon de Jesus","Ermitaño","Greenhills","Kabayanan","Maytunas","Onse","Pasadena","Rivera"],
  "Pateros": ["Aguho","Magtanggol","Martires del 96","Poblacion","San Pedro","San Roque","Santa Ana"],
  "Cainta": ["San Andres","San Isidro","San Juan","San Roque","Sto. Domingo","Sto. Niño"],
  "Taytay": ["Dolores","Muzon","San Isidro","San Juan","Sta. Ana"],
  "Angono": ["Bagumbayan","Kalayaan","Poblacion Ibaba","Poblacion Itaas","San Isidro","San Pedro","San Roque","San Vicente","Santo Niño"],
  "Antipolo": ["Bagong Nayon","Beverly Hills","Calawis","Cupang","Dalig","Dela Paz","Inarawan","Mambugan","Mayamot","Muntingdilaw","San Isidro","San Jose","San Juan","San Luis","San Roque","Santa Cruz","Santo Niño"],
  "Bacoor": ["Zapote 1","Zapote 2","Zapote 3","Talaba 1","Talaba 2","Talaba 3","Aniban 1","Aniban 2","Aniban 3","Ligas 1","Ligas 2","Maliksi 1","Maliksi 2","Panapaan 1","Panapaan 2","Panapaan 3","Panapaan 4","Panapaan 5","Pulong Gubat","Mabolo","Dulong Bayan","Habay 1","Habay 2","Sinalhan 1"],
  "Imus": ["Anabu I-A","Anabu I-B","Anabu II-A","Anabu II-B","Bucandala I","Bucandala II","Malagasang I-A","Malagasang I-B"],
  "Noveleta": ["San Rafael I","San Rafael II","San Rafael III","San Juan","Salcedo II","Poblacion"],
  "Kawit": ["Binakayan-Kanluran","Binakayan-Silangan","Marulas","Poblacion","Tabon I","Tabon II"],
  "Malolos": ["Longos","Bulihan","Sapang Bayan","Barangay 1","Barangay 2","Caingin","Cofradia"],
  "Meycauayan": ["Saluysoy","Bancal","Calvario","Camalig","Hulo","Pandayan","Pantoc"],
  "Marilao": ["Ibayo","Loma de Gato","Nagbalon","Poblacion I","Poblacion II","Prenza I","Prenza II","Santa Rosa I"],
  "Bocaue": ["Bambang","Batia","Bibigting","Bunlo","Poblacion","Taal","Turo"],
  "Hagonoy": ["Poblacion","Santo Niño","San Isidro","San Juan","San Pascual","San Pedro","San Roque","San Sebastian"],
  "Calumpit": ["Gatbuca","San Miguel","Frances","Iba O' Este","Calizon","San Jose","Poblacion"],
  "San Fernando (Pampanga)": ["Barangay 1","Barangay 2","Barangay 3","Del Pilar","San Jose","Sto. Rosario","Dolores"],
  "Angeles": ["Balibago","Cutcut","Pampang","Pulung Cacutud","Salapungan","Sto. Rosario"],
  "Calamba": ["Bagong Kalsada","Banadero","Barandal","Bucal","Canlubang","Halang","Lecheria","Pansol","Poblacion"],
  "San Pedro": ["Landayan","Nueva","San Antonio","San Roque","San Vicente","Sto. Niño","United Bayanihan"],
  "Santa Rosa": ["Aplaya","Caingin","Sinalhan","Ibaba","Kanluran","Labas","Malusak","Market Area","Pooc"],
  "Biñan": ["Dela Paz","Malaban","San Josef","Bungahan","Canlalay","Casile","Langkiwa","Malamig"],
  "Mandaue": ["Tipolo","Subangdaku","Opao","Alang-Alang","Bakilid","Banilad"],
  "Lapu-Lapu": ["Poblacion","Basak","Maribago","Mactan","Gun-ob"],
  "Baguio": ["Session Road","Burnham","Mines View","Camp John Hay"],
  "Cagayan de Oro": ["Poblacion","Cogon","Lapasan","Macasandig","Carmen"],
  "Zamboanga City": ["Poblacion","Tetuan","Guiwan","Sta. Maria","San Jose Gusu"],
  "General Santos": ["Poblacion","Lagao","Dadiangas","Bula","Labangal"]
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