<?php
/**
 * Car Colors Catalog — Philippine market
 * Data sourced from Toyota Motor Philippines brochure archives.
 *
 * Structure: CAR_COLORS[brand][model] = {
 *    _default: [colors...]         // used when year has no specific override
 *    YYYY:     [colors...]         // exact year match takes priority
 * }
 */
header('Content-Type: application/javascript; charset=utf-8');
?>
window.CAR_COLORS = {
  "Toyota": {
    "Vios": {
      _default: ["Super White","Platinum White Pearl","Silver Metallic","Gray Metallic","Attitude Black","Red Mica Metallic"],
      "2014": ["Super White","Silver Metallic","Gray Metallic","Attitude Black","Red Mica Metallic","Alumina Jade Metallic","Blackish Red Mica"],
      "2015": ["Super White","Silver Metallic","Gray Metallic","Attitude Black","Red Mica Metallic","Alumina Jade Metallic","Blackish Red Mica"],
      "2016": ["Super White","Silver Metallic","Gray Metallic","Attitude Black","Red Mica Metallic","Alumina Jade Metallic","Blackish Red Mica"],
      "2017": ["Super White","Silver Metallic","Gray Metallic","Attitude Black","Red Mica Metallic","Alumina Jade Metallic"],
      "2018": ["Platinum White Pearl","Super White","Silver Metallic","Gray Metallic","Attitude Black","Red Mica Metallic","Orange Metallic"],
      "2019": ["Platinum White Pearl","Super White","Silver Metallic","Gray Metallic","Attitude Black","Red Mica Metallic","Orange Metallic"],
      "2020": ["Platinum White Pearl","Super White","Silver Metallic","Gray Metallic","Attitude Black","Red Mica Metallic"],
      "2021": ["Platinum White Pearl","Super White","Silver Metallic","Gray Metallic","Attitude Black","Red Mica Metallic"],
      "2022": ["Platinum White Pearl","Super Red V","Silver Metallic","Gray Metallic","Attitude Black","Greenish Cyan Metallic"],
      "2023": ["Platinum White Pearl","Super Red V","Silver Metallic","Gray Metallic","Attitude Black","Greenish Cyan Metallic"],
      "2024": ["Platinum White Pearl","Super Red V","Silver Metallic","Gray Metallic","Attitude Black","Greenish Cyan Metallic"]
    },
    "Innova": {
      _default: ["Super White","White Pearl Crystal Shine","Silver Metallic","Blackish Red Mica","Alumina Jade Metallic","Attitude Black","Bronze Mica Metallic"],
      "2015": ["Super White","Silver Metallic","Blackish Red Mica","Alumina Jade Metallic","Attitude Black","Bronze Mica Metallic"],
      "2016": ["Super White","White Pearl Crystal Shine","Silver Metallic","Blackish Red Mica","Alumina Jade Metallic","Attitude Black","Bronze Mica Metallic"],
      "2017": ["Super White","White Pearl Crystal Shine","Silver Metallic","Blackish Red Mica","Alumina Jade Metallic","Attitude Black","Bronze Mica Metallic"],
      "2018": ["Super White","White Pearl Crystal Shine","Silver Metallic","Blackish Red Mica","Alumina Jade Metallic","Attitude Black","Bronze Mica Metallic"],
      "2019": ["Super White","White Pearl Crystal Shine","Silver Metallic","Blackish Red Mica","Alumina Jade Metallic","Attitude Black","Bronze Mica Metallic"],
      "2020": ["Super White","White Pearl Crystal Shine","Silver Metallic","Blackish Red Mica","Alumina Jade Metallic","Attitude Black","Bronze Mica Metallic"],
      "2021": ["Super White","White Pearl Crystal Shine","Silver Metallic","Blackish Red Mica","Attitude Black","Phantom Brown Metallic"],
      "2022": ["Super White","White Pearl Crystal Shine","Silver Metallic","Blackish Red Mica","Attitude Black","Phantom Brown Metallic"],
      "2023": ["Super White","White Pearl Crystal Shine","Silver Metallic","Attitude Black","Phantom Brown Metallic"],
      "2024": ["Super White","White Pearl Crystal Shine","Silver Metallic","Attitude Black","Phantom Brown Metallic"]
    },
    "Fortuner": {
      _default: ["Super White","White Pearl","Silver Metallic","Attitude Black Mica","Gray Metallic","Phantom Brown Metallic","Nebula Blue Metallic"],
      "2016": ["Super White","White Pearl","Silver Metallic","Attitude Black Mica","Gray Metallic","Phantom Brown Metallic","Nebula Blue Metallic"],
      "2017": ["Super White","White Pearl","Silver Metallic","Attitude Black Mica","Gray Metallic","Phantom Brown Metallic","Nebula Blue Metallic"],
      "2018": ["Super White","White Pearl","Silver Metallic","Attitude Black Mica","Gray Metallic","Phantom Brown Metallic","Nebula Blue Metallic"],
      "2019": ["Super White","White Pearl","Silver Metallic","Attitude Black Mica","Gray Metallic","Phantom Brown Metallic","Nebula Blue Metallic"],
      "2020": ["Super White","White Pearl","Silver Metallic","Attitude Black Mica","Gray Metallic","Phantom Brown Metallic","Sparkling Black Pearl"],
      "2021": ["Super White","White Pearl","Silver Metallic","Attitude Black Mica","Gray Metallic","Phantom Brown Metallic","Sparkling Black Pearl"],
      "2022": ["Super White","White Pearl","Silver Metallic","Attitude Black Mica","Gray Metallic","Phantom Brown Metallic"],
      "2023": ["Super White","White Pearl","Silver Metallic","Attitude Black Mica","Gray Metallic","Phantom Brown Metallic"],
      "2024": ["Super White","White Pearl","Silver Metallic","Attitude Black Mica","Gray Metallic","Phantom Brown Metallic"]
    },
    "Hilux": {
      _default: ["Super White","Silver Metallic","Gray Metallic","Attitude Black Mica","Crimson Spark Red Metallic","Nebula Blue","Emotional Red"],
      "2016": ["Super White","Silver Metallic","Gray Metallic","Attitude Black Mica","Crimson Spark Red Metallic","Nebula Blue"],
      "2017": ["Super White","Silver Metallic","Gray Metallic","Attitude Black Mica","Crimson Spark Red Metallic","Nebula Blue"],
      "2018": ["Super White","Silver Metallic","Gray Metallic","Attitude Black Mica","Crimson Spark Red Metallic","Nebula Blue"],
      "2019": ["Super White","Silver Metallic","Gray Metallic","Attitude Black Mica","Crimson Spark Red Metallic","Nebula Blue"],
      "2020": ["Super White","Silver Metallic","Gray Metallic","Attitude Black Mica","Crimson Spark Red Metallic","Nebula Blue","Emotional Red"],
      "2021": ["Super White","Silver Metallic","Gray Metallic","Attitude Black Mica","Crimson Spark Red Metallic","Nebula Blue","Emotional Red"],
      "2022": ["Super White","Silver Metallic","Gray Metallic","Attitude Black Mica","Crimson Spark Red Metallic","Nebula Blue","Emotional Red"],
      "2023": ["Super White","Silver Metallic","Gray Metallic","Attitude Black Mica","Crimson Spark Red Metallic","Nebula Blue","Emotional Red"],
      "2024": ["Super White","Silver Metallic","Gray Metallic","Attitude Black Mica","Crimson Spark Red Metallic","Nebula Blue","Emotional Red"]
    },
    "Wigo": {
      _default: ["Orange Metallic","Silver Metallic","Gray Metallic","White","Black","Yellow","Red Mica Metallic"],
      "2014": ["Orange Metallic","Silver Metallic","Gray Metallic","White","Black","Yellow","Red Mica Metallic"],
      "2015": ["Orange Metallic","Silver Metallic","Gray Metallic","White","Black","Yellow","Red Mica Metallic"],
      "2016": ["Orange Metallic","Silver Metallic","Gray Metallic","White","Black","Yellow","Red Mica Metallic"],
      "2017": ["Orange Metallic","Silver Metallic","Gray Metallic","White","Black","Yellow","Red Mica Metallic"],
      "2018": ["Orange Metallic","Silver Metallic","Gray Metallic","White","Black","Red Mica Metallic"],
      "2019": ["Orange Metallic","Silver Metallic","Gray Metallic","White","Black","Red Mica Metallic"],
      "2020": ["Orange Metallic","Silver Metallic","Gray Metallic","White","Black","Red Mica Metallic"],
      "2021": ["Orange Metallic","Silver Metallic","Gray Metallic","White","Black","Red Mica Metallic"],
      "2022": ["Orange Metallic","Silver Metallic","Gray Metallic","White","Black","Red Mica Metallic"],
      "2023": ["Orange Metallic","Silver Metallic","Gray Metallic","White","Black","Yellow","Red Mica Metallic"],
      "2024": ["Orange Metallic","Silver Metallic","Gray Metallic","White","Black","Yellow","Red Mica Metallic"]
    },
    "Avanza": {
      _default: ["Silver Mica Metallic","Gray Metallic","Black Metallic","Dark Red Mica","White","Nebula Blue","Green Mica"],
      "2015": ["Silver Mica Metallic","Gray Metallic","Black Metallic","Dark Red Mica","White","Nebula Blue","Green Mica"],
      "2016": ["Silver Mica Metallic","Gray Metallic","Black Metallic","Dark Red Mica","White","Nebula Blue","Green Mica"],
      "2017": ["Silver Mica Metallic","Gray Metallic","Black Metallic","Dark Red Mica","White","Nebula Blue","Green Mica"],
      "2018": ["Silver Mica Metallic","Gray Metallic","Black Metallic","Dark Red Mica","White","Nebula Blue"],
      "2019": ["Silver Mica Metallic","Gray Metallic","Black Metallic","Dark Red Mica","White","Nebula Blue"],
      "2020": ["Silver Mica Metallic","Gray Metallic","Black Metallic","Dark Red Mica","White","Nebula Blue"],
      "2021": ["Silver Mica Metallic","Gray Metallic","Black Metallic","Dark Red Mica","White","Nebula Blue"],
      "2022": ["Silver Mica Metallic","Gray Metallic","Black Metallic","Dark Red Mica","White","Nebula Blue"],
      "2023": ["Silver Mica Metallic","Gray Metallic","Black Metallic","Dark Red Mica","White","Nebula Blue"],
      "2024": ["Silver Mica Metallic","Gray Metallic","Black Metallic","Dark Red Mica","White","Nebula Blue"]
    }
  },
  // Non-Toyota brands use a per-model default only (year overrides optional)
  "Honda": {
    "City":    { _default: ["Taffeta White","Lunar Silver","Modern Steel","Crystal Black","Platinum White Pearl","Rallye Red"] },
    "Civic":   { _default: ["Platinum White Pearl","Lunar Silver","Modern Steel","Crystal Black","Rallye Red","Sonic Gray Pearl"] },
    "Brio":    { _default: ["Taffeta White","Lunar Silver","Modern Steel","Phoenix Orange","Rallye Red","Crystal Black"] },
    "BR-V":    { _default: ["Taffeta White","Lunar Silver","Modern Steel","Crystal Black","Premium Amber"] }
  },
  "Mitsubishi": {
    "Montero Sport": { _default: ["White Pearl","Sterling Silver","Graphite Gray","Jet Black","Sunflare Orange","Blade Silver"] },
    "Mirage G4":     { _default: ["Cool Silver","Pearl White","Graphite Gray","Wine Red","Mercury Gray","Infrared"] },
    "Xpander":       { _default: ["Quartz White Pearl","Sterling Silver","Graphite Gray","Jet Black","Solar Orange","Blade Silver"] },
    "Strada":        { _default: ["White Diamond","Sterling Silver","Jet Black","Sunflare Orange","Graphite Gray","Blade Silver"] },
    "L300":          { _default: ["Polar White","Silver","Black"] }
  },
  "Nissan": {
    "Navara":  { _default: ["Brilliant Silver","Pearl White","Cosmic Black","Earthy Bronze","Solar Orange"] },
    "Almera":  { _default: ["Pearl White","Brilliant Silver","Gun Metallic","Super Black","Radiant Red"] },
    "Terra":   { _default: ["Pearl White","Brilliant Silver","Gun Metallic","Black","Solar Orange"] },
    "Urvan":   { _default: ["Brilliant Silver","Pearl White","Gun Metallic","Black"] }
  },
  "Ford": {
    "Ranger":    { _default: ["Arctic White","Absolute Black","Magnetic Metallic","Sedona Orange","Blue Lightning","Aluminium Metallic"] },
    "Everest":   { _default: ["Arctic White","Absolute Black","Magnetic Metallic","Sedona Orange","Blue Lightning","Aluminium Metallic"] },
    "Territory": { _default: ["Arctic White","Absolute Black","Blue Metallic","Panther Black","Desert Brown"] },
    "EcoSport":  { _default: ["Arctic White","Absolute Black","Moondust Silver","Canyon Ridge","Race Red","Blue Lightning"] }
  },
  "Hyundai": {
    "Accent":  { _default: ["Polar White","Sleek Silver","Phantom Black","Fiery Red","Admiral Blue"] },
    "Tucson":  { _default: ["Polar White","Sleek Silver","Phantom Black","Fiery Red","Admiral Blue","Amazon Gray"] }
  }
};

/**
 * Lookup colors for a brand+model+year combination.
 * Priority: exact year → _default → empty array
 */
window.getCarColors = function(brand, model, year) {
  const b = window.CAR_COLORS[brand];
  if (!b) return [];
  const m = b[model];
  if (!m) return [];
  const yr = String(year);
  if (m[yr]) return m[yr];
  if (m._default) return m._default;
  return [];
};