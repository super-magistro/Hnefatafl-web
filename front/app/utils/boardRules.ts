export interface BoardRules {
  win_condition?: string
  king_capture?: string
  king_weapon?: string
  throne_hostility?: string
  shieldWall?: boolean
  exitForts?: boolean
}

export interface BoardRulesSummary {
  winCondition: string
  kingCapture: string
  kingWeapon: string
  throneHostility: string
}

/**
 * Traduit les règles d'un plateau de jeu (depuis le format API)
 * en libellés compréhensibles et uniformisés pour l'interface.
 */
export function translateBoardRules(rules: BoardRules = {}): BoardRulesSummary {
  let winConditionText = 'Bords'
  if (rules.win_condition === 'corner') winConditionText = 'Coins (4)'
  else if (rules.win_condition === 'edge') winConditionText = 'Bords'
  
  let kingCaptureText = '4 côtés'
  if (rules.king_capture === '2_sides') kingCaptureText = '2 côtés (Roi faible)'
  else if (rules.king_capture === '4_sides') kingCaptureText = '4 côtés (Roi fort)'
  
  let kingWeaponText = 'Armé'
  if (rules.king_weapon === 'unarmed' || rules.king_weapon === 'weak') kingWeaponText = 'Désarmé'
  else if (rules.king_weapon === 'armed') kingWeaponText = 'Armé'
  
  let throneHostilityText = 'Hostile vide'
  if (rules.throne_hostility === 'always') throneHostilityText = 'Toujours hostile'
  else if (rules.throne_hostility === 'empty') throneHostilityText = 'Hostile vide'
  else if (rules.throne_hostility === 'never') throneHostilityText = 'Jamais hostile'

  return {
    winCondition: winConditionText,
    kingCapture: kingCaptureText,
    kingWeapon: kingWeaponText,
    throneHostility: throneHostilityText
  }
}
