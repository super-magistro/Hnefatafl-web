// front/app/game-types/index.ts
export interface User {
  id?: number;
  '@id'?: string;
  email?: string;
  elo?: number;
  // add other fields as needed
}

export interface GameBoard {
  id?: number;
  '@id'?: string;
  name: string;
  boardSize: number;
  initialLayout: number[][];
  terrainLayout: number[][];
  description?: string;
  rules?: Record<string, any>;
}

export interface Game {
  id: number;
  variant: string;
  timeControl: string;
  status: string; // PLAYING | PENDING | FINISHED
  attacker?: string | null;
  defender?: string | null;
  boardState?: number[][];
  gameBoard?: string;
  moves?: any[];
  winner?: string | null;
  // other fields from API
}
