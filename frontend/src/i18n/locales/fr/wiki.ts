export default {
  categories: {
    spells: 'Sorts',
    races: 'Races',
    classes: 'Classes',
    backgrounds: 'Historiques',
    feats: 'Dons',
    items: 'Équipement',
    monsters: 'Monstres',
    favorites: 'Mes favoris',
  },

  home: {
    title: 'Wiki D&D 5e',
    subtitle: 'Encyclopédie des règles, sorts, monstres et plus encore',
    searchPlaceholder: 'Rechercher un sort, monstre, classe...',
    searchButton: 'Rechercher',
    favoritesTitle: 'Mes favoris',
    favoritesDescription: 'Retrouvez vos sorts, classes et monstres favoris',
    favoritesViewAll: 'Voir tout \u2192',
  },

  categoryCard: {
    browse: 'Parcourir \u2192',
  },

  categoryView: {
    filtersButton: 'Filtres',
    filtersHeading: 'Filtres',
    emptyState: 'Aucun résultat',
    selectItemHint: 'Sélectionnez un élément pour voir ses détails',
    source: 'Source :',
    page: 'Page',
  },

  favorite: {
    add: 'Ajouter aux favoris',
    remove: 'Retirer des favoris',
  },

  filters: {
    search: 'Recherche',
    searchPlaceholder: 'Nom...',
    source: 'Source',
    sourcePlaceholder: 'PHB, MM, XGE...',
  },

  spellFilters: {
    level: 'Niveau',
    allLevels: 'Tous les niveaux',
    cantrip: 'Tour de magie',
    levelN: 'Niveau {n}',
    school: 'École',
    allSchools: 'Toutes les écoles',
    damageType: 'Type de dégâts',
    allDamageTypes: 'Tous',
  },

  raceFilters: {
    speedType: 'Type de mouvement',
    allSpeedTypes: 'Tous',
    vision: 'Vision',
    allVisions: 'Toutes',
  },

  monsterFilters: {
    cr: 'Indice de danger',
    allCR: 'Tous les ID',
    crLabel: 'ID {cr}',
    size: 'Taille',
    allSizes: 'Toutes les tailles',
  },

  itemFilters: {
    rarity: 'Rareté',
    allRarities: 'Toutes',
  },

  speedTypes: {
    fly: 'Vol',
    swim: 'Nage',
    climb: 'Escalade',
    burrow: 'Fouissement',
  },

  visionTypes: {
    darkvision: 'Vision dans le noir',
    superiorDarkvision: 'Vision dans le noir supérieure',
    blindsight: 'Vision aveugle',
    truesight: 'Vrai véritable',
    tremorsense: 'Perception des vibrations',
  },

  spellCard: {
    cantrip: 'Tour',
    levelAbbr: 'Niv.',
  },

  itemCard: {
    level: 'Niveau',
    cr: 'ID',
  },

  spellDetail: {
    spellSubtitle: 'Tour de magie — {school}',
    spellSubtitleLevel: 'Sort de {school} de niveau {level}',
    spellSubtitleNoSchool: 'Tour de magie',
    spellSubtitleLevelNoSchool: 'Sort de niveau {level}',
    similarSubtitle: 'Tour de magie — {school}',
    similarSubtitleLevel: 'Sort de {school} de niveau {level}',
    similarSubtitleLevelZero: 'Sort de {school} de niveau 0 (cantrip)',

    level: 'Niveau',
    cantrip: 'Tour',
    damage: 'Dégâts',
    range: 'Portée',
    duration: 'Durée',

    components: 'Composants',
    verbal: 'Verbal',
    somatic: 'Somatique',
    material: 'Matériel',

    description: 'Description',
    damageCalculator: 'Calculateur de dégâts',
    classes: 'Classes',
    sources: 'Sources :',
    similarSpells: 'Sorts similaires',
  },

  damageCalculator: {
    spellLevel: 'Niveau du sort :',
    damageOf: 'dégâts de {type}',
    min: 'Min :',
    avg: 'Moyenne :',
    max: 'Max :',
  },

  raceDetail: {
    size: 'Taille',
    speed: 'Vitesse',
    source: 'Source',
    racialTraits: 'Traits raciaux',
    subraces: 'Sous-races',
  },

  classDetail: {
    hitDie: 'Dé de vie',
    savingThrows: 'Jets de sauvegarde',
    spellcasting: 'Magie',

    tabs: {
      progression: 'Progression',
      features: 'Capacités',
      info: 'Informations',
      subclasses: 'Sous-classes',
    },

    progressionTable: {
      level: 'Niv.',
      proficiencyBonus: 'Bon. maîtrise',
      features: 'Capacités',
    },

    noFeatures: 'Aucune capacité disponible.',
    levelN: 'Niveau {level}',

    proficiencies: {
      heading: 'Maîtrises de départ',
      armor: 'Armures',
      weapons: 'Armes',
      tools: 'Outils',
    },

    startingEquipment: 'Équipement de départ',

    multiclassing: {
      heading: 'Multiclassage',
      prerequisites: 'Prérequis :',
    },

    subclassSelectHint: 'Sélectionne une sous-classe pour voir sa description.',
    page: 'Page',
  },

  itemDetail: {
    magical: 'Magique',
    attunement: 'Harmonisation',
    value: 'Valeur',
    goldPieces: 'po',
    weight: 'Poids',
    weightUnit: 'kg',
    ac: 'CA',
  },

  monsterDetail: {
    cr: 'Indice de danger',
    ac: 'CA',
    hp: 'PV',
    speed: 'Vitesse',

    abilities: {
      str: 'FOR',
      dex: 'DEX',
      con: 'CON',
      int: 'INT',
      wis: 'SAG',
      cha: 'CHA',
    },

    resistances: 'Résistances :',
    damageImmunities: 'Immunités (dégâts) :',
    conditionImmunities: 'Immunités (états) :',

    senses: 'Sens :',
    passivePerception: 'Perception passive',
    languages: 'Langues :',

    sections: {
      traits: 'Traits',
      actions: 'Actions',
      reactions: 'Réactions',
      legendaryActions: 'Actions légendaires',
    },
  },

  backgroundDetail: {
    skills: 'Compétences :',
    startingEquipment: 'Équipement de départ :',
  },

  featDetail: {
    prerequisite: 'Prérequis :',
  },

  reference: {
    spellSchool: {
      abjuration: 'Abjuration',
      conjuration: 'Invocation',
      divination: 'Divination',
      enchantment: 'Enchantement',
      evocation: 'Évocation',
      illusion: 'Illusion',
      necromancy: 'Nécromancie',
      transmutation: 'Transmutation',
    },
    damageType: {
      acid: 'Acide',
      bludgeoning: 'Contondant',
      cold: 'Froid',
      fire: 'Feu',
      force: 'Force',
      lightning: 'Foudre',
      necrotic: 'Nécrotique',
      piercing: 'Perforant',
      poison: 'Poison',
      psychic: 'Psychique',
      radiant: 'Radiant',
      slashing: 'Tranchant',
      thunder: 'Tonnerre',
    },
    creatureSize: {
      tiny: 'Très petit (TP)',
      small: 'Petit (P)',
      medium: 'Moyen (M)',
      large: 'Grand (G)',
      huge: 'Très grand (TG)',
      gargantuan: 'Gigantesque (Gig)',
    },
    creatureType: {
      aberration: 'Aberration',
      beast: 'Bête',
      celestial: 'Céleste',
      construct: 'Artificiel',
      dragon: 'Dragon',
      elemental: 'Élémentaire',
      fey: 'Fée',
      fiend: 'Fiélon',
      giant: 'Géant',
      humanoid: 'Humanoïde',
      monstrosity: 'Monstruosité',
      ooze: 'Vase',
      plant: 'Plante',
      undead: 'Mort-vivant',
    },
    alignment: {
      'lawful-good': 'Loyal Bon',
      'neutral-good': 'Neutre Bon',
      'chaotic-good': 'Chaotique Bon',
      'lawful-neutral': 'Loyal Neutre',
      'true-neutral': 'Neutre',
      'chaotic-neutral': 'Chaotique Neutre',
      'lawful-evil': 'Loyal Mauvais',
      'neutral-evil': 'Neutre Mauvais',
      'chaotic-evil': 'Chaotique Mauvais',
      unaligned: 'Sans alignement',
      any: 'Tout alignement',
    },
    itemCategory: {
      weapon: 'Arme',
      armor: 'Armure',
      'adventuring-gear': 'Équipement d\'aventurier',
      tool: 'Outil',
      'mount-vehicle': 'Monture et véhicule',
      'trade-good': 'Marchandise',
      treasure: 'Trésor',
      'wondrous-item': 'Objet merveilleux',
      potion: 'Potion',
      ring: 'Anneau',
      rod: 'Baguette',
      scroll: 'Parchemin',
      staff: 'Bâton',
      wand: 'Baguette magique',
      ammunition: 'Munition',
      shield: 'Bouclier',
    },
    itemRarity: {
      none: 'Aucune',
      common: 'Courante',
      uncommon: 'Peu courante',
      rare: 'Rare',
      'very-rare': 'Très rare',
      legendary: 'Légendaire',
      artifact: 'Artéfact',
      varies: 'Variable',
    },
  },

  errors: {
    loadingError: 'Erreur de chargement',
  },
}
