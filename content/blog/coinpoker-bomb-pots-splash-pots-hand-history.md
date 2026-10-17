---
title: "CoinPoker Bomb Pots and Splash Pots in Your Hand History"
slug: coinpoker-bomb-pots-splash-pots-hand-history
excerpt: "Bomb pots skip preflop and splash pots add free money. Here's how each looks in a CoinPoker hand history and why they can distort your stats."
meta_title: "CoinPoker Bomb Pots and Splash Pots in Hand Histories"
meta_description: "How CoinPoker bomb pots and splash pots appear in hand histories, what makes them different, and how to keep them from skewing your tracker stats."
published: true
published_at: "2026-10-31T09:00:00+00:00"
---

## Two special pots, two different effects

CoinPoker's cash games have two features that change the hand and leave a mark in the hand history. They are easy to mix up.

| | Bomb pot | Splash pot |
|---|---|---|
| Where the money comes from | The players, as an automatic ante | A cash drop funded by CoinPoker |
| Preflop betting | Skipped: the hand starts on the flop | Normal |
| Boards | Double boards | One board |

CoinPoker explains them in its own help pages: [what is a bomb pot](https://coinpoker.com/help/bomb-pot/) and [what are splash pots](https://coinpoker.com/help/splash/). The exact amounts depend on the game, so check those pages for current details.

## How a bomb pot looks in the file

The header names the game type, and every player posts an ante instead of blinds:

```
CoinPoker Hand #129794200777: NLH BombPot (₮0.01/₮0.02) 2026/09/09 12:45:02 CEST
Hero: posts the ante ₮0.10
070fbf52: posts the ante ₮0.10
...
*** HOLE CARDS ***
*** FLOP *** [Kh 7c 2s]
```

There is **no preflop action at all**: the cards are dealt and the flop follows immediately. When a bomb pot runs two boards, the summary reads `Hand was run with two boards`.

## How a splash pot looks in the file

A splash pot plays like a normal hand, with an extra line before the blinds showing the money that was dropped in:

```
SPLASH dropped ₮0.04
2e9886c6: posts small blind ₮0.01
4f1fcfb2: posts big blind ₮0.02
```

On some tables you'll also see a **Splash Fee** next to the rake in the summary, which is a frequent cause of rejected imports. [Fixing "Invalid Pot Size" errors](/blog/fix-invalid-pot-size-error-coinpoker-import) explains it.

## Why they can distort your stats

- **Bomb pots have no preflop action.** Stats such as VPIP and PFR describe preflop choices, and there are none in a bomb pot. How your tracker counts these hands is worth checking, so your preflop numbers aren't diluted.
- **Pot sizes jump.** An ante from every player, or dropped cash, makes the pot bigger than the action suggests. That can inflate showdown and win-rate figures for a few hands.
- **Rake and fees.** Splash money has its own fee, so rake per hand isn't uniform. [What rake is and how it affects your win rate](/blog/what-is-rake-in-online-poker) covers the basics.

## Count them without losing them

[PokerHandsConverter](/) counts splash pots and bomb pots in each file it converts, so you can see how many hands in a session were special. The result page shows both counts next to the total number of hands.

Once your hands are ready, import them. [How to import CoinPoker hands into PokerTracker 4](/blog/import-coinpoker-hands-pokertracker-4) shows how.
