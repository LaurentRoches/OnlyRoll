export default {
  categories: {
    spells: 'Spells',
    races: 'Races',
    classes: 'Classes',
    backgrounds: 'Backgrounds',
    feats: 'Feats',
    items: 'Equipment',
    monsters: 'Monsters',
    favorites: 'My favorites',
  },

  home: {
    title: 'D&D 5e Wiki',
    subtitle: 'Encyclopedia of rules, spells, monsters and more',
    searchPlaceholder: 'Search for a spell, monster, class...',
    searchButton: 'Search',
    favoritesTitle: 'My favorites',
    favoritesDescription: 'Find your favorite spells, classes and monsters',
    favoritesViewAll: 'View all \u2192',
  },

  categoryCard: {
    browse: 'Browse \u2192',
  },

  categoryView: {
    filtersButton: 'Filters',
    filtersHeading: 'Filters',
    emptyState: 'No results',
    selectItemHint: 'Select an item to view its details',
    source: 'Source:',
    page: 'Page',
  },

  favorite: {
    add: 'Add to favorites',
    remove: 'Remove from favorites',
  },

  filters: {
    search: 'Search',
    searchPlaceholder: 'Name...',
    source: 'Source',
    sourcePlaceholder: 'PHB, MM, XGE...',
  },

  spellFilters: {
    level: 'Level',
    allLevels: 'All levels',
    cantrip: 'Cantrip',
    levelN: 'Level {n}',
    school: 'School',
    allSchools: 'All schools',
    damageType: 'Damage type',
    allDamageTypes: 'All',
  },

  raceFilters: {
    speedType: 'Movement type',
    allSpeedTypes: 'All',
    vision: 'Vision',
    allVisions: 'All',
  },

  monsterFilters: {
    cr: 'Challenge rating',
    allCR: 'All CRs',
    crLabel: 'CR {cr}',
    size: 'Size',
    allSizes: 'All sizes',
  },

  itemFilters: {
    rarity: 'Rarity',
    allRarities: 'All',
  },

  speedTypes: {
    fly: 'Fly',
    swim: 'Swim',
    climb: 'Climb',
    burrow: 'Burrow',
  },

  visionTypes: {
    darkvision: 'Darkvision',
    superiorDarkvision: 'Superior Darkvision',
    blindsight: 'Blindsight',
    truesight: 'Truesight',
    tremorsense: 'Tremorsense',
  },

  spellCard: {
    cantrip: 'Cantrip',
    levelAbbr: 'Lvl.',
  },

  itemCard: {
    level: 'Level',
    cr: 'CR',
  },

  spellDetail: {
    spellSubtitle: '{school} Cantrip',
    spellSubtitleLevel: 'Level {level} {school} Spell',
    spellSubtitleNoSchool: 'Cantrip',
    spellSubtitleLevelNoSchool: 'Level {level} Spell',
    similarSubtitle: '{school} Cantrip',
    similarSubtitleLevel: 'Level {level} {school} Spell',
    similarSubtitleLevelZero: 'Level 0 {school} Spell (cantrip)',

    level: 'Level',
    cantrip: 'Cantrip',
    damage: 'Damage',
    range: 'Range',
    duration: 'Duration',

    components: 'Components',
    verbal: 'Verbal',
    somatic: 'Somatic',
    material: 'Material',

    description: 'Description',
    damageCalculator: 'Damage calculator',
    classes: 'Classes',
    sources: 'Sources:',
    similarSpells: 'Similar spells',
  },

  damageCalculator: {
    spellLevel: 'Spell level:',
    damageOf: '{type} damage',
    min: 'Min:',
    avg: 'Average:',
    max: 'Max:',
  },

  raceDetail: {
    size: 'Size',
    speed: 'Speed',
    source: 'Source',
    racialTraits: 'Racial traits',
    subraces: 'Subraces',
  },

  classDetail: {
    hitDie: 'Hit die',
    savingThrows: 'Saving throws',
    spellcasting: 'Spellcasting',

    tabs: {
      progression: 'Progression',
      features: 'Features',
      info: 'Information',
      subclasses: 'Subclasses',
    },

    progressionTable: {
      level: 'Lvl.',
      proficiencyBonus: 'Prof. bonus',
      features: 'Features',
    },

    noFeatures: 'No features available.',
    levelN: 'Level {level}',

    proficiencies: {
      heading: 'Starting proficiencies',
      armor: 'Armor',
      weapons: 'Weapons',
      tools: 'Tools',
    },

    startingEquipment: 'Starting equipment',

    multiclassing: {
      heading: 'Multiclassing',
      prerequisites: 'Prerequisites:',
    },

    subclassSelectHint: 'Select a subclass to view its description.',
    page: 'Page',
  },

  itemDetail: {
    magical: 'Magical',
    attunement: 'Attunement',
    value: 'Value',
    goldPieces: 'gp',
    weight: 'Weight',
    weightUnit: 'lbs',
    ac: 'AC',
  },

  monsterDetail: {
    cr: 'Challenge rating',
    ac: 'AC',
    hp: 'HP',
    speed: 'Speed',

    abilities: {
      str: 'STR',
      dex: 'DEX',
      con: 'CON',
      int: 'INT',
      wis: 'WIS',
      cha: 'CHA',
    },

    resistances: 'Resistances:',
    damageImmunities: 'Immunities (damage):',
    conditionImmunities: 'Immunities (conditions):',

    senses: 'Senses:',
    passivePerception: 'Passive perception',
    languages: 'Languages:',

    sections: {
      traits: 'Traits',
      actions: 'Actions',
      reactions: 'Reactions',
      legendaryActions: 'Legendary actions',
    },
  },

  backgroundDetail: {
    skills: 'Skills:',
    startingEquipment: 'Starting equipment:',
  },

  featDetail: {
    prerequisite: 'Prerequisite:',
  },

  reference: {
    spellSchool: {
      abjuration: 'Abjuration',
      conjuration: 'Conjuration',
      divination: 'Divination',
      enchantment: 'Enchantment',
      evocation: 'Evocation',
      illusion: 'Illusion',
      necromancy: 'Necromancy',
      transmutation: 'Transmutation',
    },
    damageType: {
      acid: 'Acid',
      bludgeoning: 'Bludgeoning',
      cold: 'Cold',
      fire: 'Fire',
      force: 'Force',
      lightning: 'Lightning',
      necrotic: 'Necrotic',
      piercing: 'Piercing',
      poison: 'Poison',
      psychic: 'Psychic',
      radiant: 'Radiant',
      slashing: 'Slashing',
      thunder: 'Thunder',
    },
    creatureSize: {
      tiny: 'Tiny',
      small: 'Small',
      medium: 'Medium',
      large: 'Large',
      huge: 'Huge',
      gargantuan: 'Gargantuan',
    },
    creatureType: {
      aberration: 'Aberration',
      beast: 'Beast',
      celestial: 'Celestial',
      construct: 'Construct',
      dragon: 'Dragon',
      elemental: 'Elemental',
      fey: 'Fey',
      fiend: 'Fiend',
      giant: 'Giant',
      humanoid: 'Humanoid',
      monstrosity: 'Monstrosity',
      ooze: 'Ooze',
      plant: 'Plant',
      undead: 'Undead',
    },
    alignment: {
      'lawful-good': 'Lawful Good',
      'neutral-good': 'Neutral Good',
      'chaotic-good': 'Chaotic Good',
      'lawful-neutral': 'Lawful Neutral',
      'true-neutral': 'True Neutral',
      'chaotic-neutral': 'Chaotic Neutral',
      'lawful-evil': 'Lawful Evil',
      'neutral-evil': 'Neutral Evil',
      'chaotic-evil': 'Chaotic Evil',
      unaligned: 'Unaligned',
      any: 'Any Alignment',
    },
    itemCategory: {
      weapon: 'Weapon',
      armor: 'Armor',
      'adventuring-gear': 'Adventuring Gear',
      tool: 'Tool',
      'mount-vehicle': 'Mount & Vehicle',
      'trade-good': 'Trade Good',
      treasure: 'Treasure',
      'wondrous-item': 'Wondrous Item',
      potion: 'Potion',
      ring: 'Ring',
      rod: 'Rod',
      scroll: 'Scroll',
      staff: 'Staff',
      wand: 'Wand',
      ammunition: 'Ammunition',
      shield: 'Shield',
    },
    itemRarity: {
      none: 'None',
      common: 'Common',
      uncommon: 'Uncommon',
      rare: 'Rare',
      'very-rare': 'Very Rare',
      legendary: 'Legendary',
      artifact: 'Artifact',
      varies: 'Varies',
    },
  },

  errors: {
    loadingError: 'Loading error',
  },
}
