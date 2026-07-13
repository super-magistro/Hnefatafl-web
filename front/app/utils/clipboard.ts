/**
 * Copie un texte dans le presse-papier avec une méthode de secours (fallback) robuste pour le HTTP non-sécurisé.
 * Retourne true si la copie a réussi, false sinon.
 */
export async function copyToClipboard(text: string): Promise<boolean> {
  if (navigator.clipboard && navigator.clipboard.writeText) {
    try {
      await navigator.clipboard.writeText(text)
      return true
    } catch {
      return fallbackCopy(text)
    }
  } else {
    return fallbackCopy(text)
  }
}

function fallbackCopy(text: string): boolean {
  try {
    const textArea = document.createElement("textarea")
    textArea.value = text
    textArea.style.top = "0"
    textArea.style.left = "0"
    textArea.style.position = "fixed"
    document.body.appendChild(textArea)
    textArea.focus()
    textArea.select()
    const successful = document.execCommand('copy')
    document.body.removeChild(textArea)
    return successful
  } catch (err) {
    console.error('Erreur lors de la copie de secours (fallback) :', err)
    return false
  }
}
