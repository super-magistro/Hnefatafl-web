/**
 * Formate un timestamp ISO (ou une date) en format d'heure HH:MM.
 */
export function formatTime(isoString: string | Date): string {
  if (!isoString) return ''
  const date = new Date(isoString)
  return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
}

/**
 * Traduit une notation de coup technique (ex: e-att-3-0-3-4 ou c-def-5-5-5-1) 
 * en un libellé textuel lisible en notation classique de Hnefatafl (ex: Attaquant : D1 ➔ D5).
 */
export function getMoveLabel(move: string): string {
  const parts = move.split('-')
  if (parts.length < 6) return move
  
  const isCapture = parts[0] === 'c'
  const isEscaped = parts[0] === 'e'
  const role = parts[1] === 'att' ? 'Attaquant' : 'Défenseur'
  const fromX = parts[2]
  const fromY = parts[3]
  const toX = parts[4]
  const toY = parts[5]
  
  const fromStr = `${String.fromCharCode(65 + parseInt(fromX))}${parseInt(fromY) + 1}`
  const toStr = `${String.fromCharCode(65 + parseInt(toX))}${parseInt(toY) + 1}`
  
  let label = `${role} : ${fromStr} ➔ ${toStr}`
  if (isCapture) {
    label += ' ☠️'
  } else if (isEscaped) {
    label += ' 👑'
  }
  return label
}
