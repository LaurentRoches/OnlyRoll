export const SPELL_SCHOOLS = [
  { value: 'abjuration', labelKey: 'wiki.reference.spellSchool.abjuration' },
  { value: 'conjuration', labelKey: 'wiki.reference.spellSchool.conjuration' },
  { value: 'divination', labelKey: 'wiki.reference.spellSchool.divination' },
  { value: 'enchantment', labelKey: 'wiki.reference.spellSchool.enchantment' },
  { value: 'evocation', labelKey: 'wiki.reference.spellSchool.evocation' },
  { value: 'illusion', labelKey: 'wiki.reference.spellSchool.illusion' },
  { value: 'necromancy', labelKey: 'wiki.reference.spellSchool.necromancy' },
  { value: 'transmutation', labelKey: 'wiki.reference.spellSchool.transmutation' },
]

export const SPELL_LEVELS = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9] as const

export const DAMAGE_TYPES = [
  { value: 'acid', labelKey: 'wiki.reference.damageType.acid' },
  { value: 'bludgeoning', labelKey: 'wiki.reference.damageType.bludgeoning' },
  { value: 'cold', labelKey: 'wiki.reference.damageType.cold' },
  { value: 'fire', labelKey: 'wiki.reference.damageType.fire' },
  { value: 'force', labelKey: 'wiki.reference.damageType.force' },
  { value: 'lightning', labelKey: 'wiki.reference.damageType.lightning' },
  { value: 'necrotic', labelKey: 'wiki.reference.damageType.necrotic' },
  { value: 'piercing', labelKey: 'wiki.reference.damageType.piercing' },
  { value: 'poison', labelKey: 'wiki.reference.damageType.poison' },
  { value: 'psychic', labelKey: 'wiki.reference.damageType.psychic' },
  { value: 'radiant', labelKey: 'wiki.reference.damageType.radiant' },
  { value: 'slashing', labelKey: 'wiki.reference.damageType.slashing' },
  { value: 'thunder', labelKey: 'wiki.reference.damageType.thunder' },
]

export const MONSTER_SIZES = [
  { value: 'Tiny', labelKey: 'wiki.reference.creatureSize.tiny' },
  { value: 'Small', labelKey: 'wiki.reference.creatureSize.small' },
  { value: 'Medium', labelKey: 'wiki.reference.creatureSize.medium' },
  { value: 'Large', labelKey: 'wiki.reference.creatureSize.large' },
  { value: 'Huge', labelKey: 'wiki.reference.creatureSize.huge' },
  { value: 'Gargantuan', labelKey: 'wiki.reference.creatureSize.gargantuan' },
]

export const RACE_SPEED_TYPES = [
  { value: 'fly', labelKey: 'wiki.speedTypes.fly' },
  { value: 'swim', labelKey: 'wiki.speedTypes.swim' },
  { value: 'climb', labelKey: 'wiki.speedTypes.climb' },
  { value: 'burrow', labelKey: 'wiki.speedTypes.burrow' },
]

export const RACE_VISION_TYPES = [
  { value: 'Darkvision', labelKey: 'wiki.visionTypes.darkvision' },
  { value: 'Superior Darkvision', labelKey: 'wiki.visionTypes.superiorDarkvision' },
  { value: 'Blindsight', labelKey: 'wiki.visionTypes.blindsight' },
  { value: 'Truesight', labelKey: 'wiki.visionTypes.truesight' },
  { value: 'Tremorsense', labelKey: 'wiki.visionTypes.tremorsense' },
]

export const ITEM_RARITIES = [
  { value: 'none', labelKey: 'wiki.reference.itemRarity.none' },
  { value: 'common', labelKey: 'wiki.reference.itemRarity.common' },
  { value: 'uncommon', labelKey: 'wiki.reference.itemRarity.uncommon' },
  { value: 'rare', labelKey: 'wiki.reference.itemRarity.rare' },
  { value: 'very rare', labelKey: 'wiki.reference.itemRarity.very-rare' },
  { value: 'legendary', labelKey: 'wiki.reference.itemRarity.legendary' },
  { value: 'artifact', labelKey: 'wiki.reference.itemRarity.artifact' },
]

export const MONSTER_CR_VALUES = [
  '0', '1/8', '1/4', '1/2',
  '1', '2', '3', '4', '5', '6', '7', '8', '9', '10',
  '11', '12', '13', '14', '15', '16', '17', '18', '19', '20',
  '21', '22', '23', '24', '25', '26', '27', '28', '29', '30',
] as const
