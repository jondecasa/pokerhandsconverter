---
title: "CoinPoker's ₮ Sign: How USDT Shows Up in Hand Histories"
slug: coinpoker-usdt-currency-in-hand-histories
excerpt: "CoinPoker prices games in USDT and prints a ₮ sign with no currency code. Here's what that means for stakes, bankroll tracking and your tracker."
meta_title: "CoinPoker ₮ Currency in Hand Histories and Trackers"
meta_description: "What the ₮ (Tether) sign in CoinPoker hand histories means, why trackers misread it, and how stakes and bankroll figures are affected."
published: true
published_at: "2026-10-25T09:00:00+00:00"
---

## What you see in the file

CoinPoker prices its cash games in USDT, a stablecoin, and writes amounts with the Tether sign **₮** and no currency code:

```
CoinPoker Hand #129794100893: NLH (₮0.01/₮0.02) 2026/09/09 12:20:36 CEST
Seat 1: Hero (₮2 in chips)
2e9886c6: posts small blind ₮0.01
```

Hands from the PokerStars family use a normal currency symbol (`$`, `€`) and often a code, for example `($0.01/$0.02 USD)`. A tracker's importer is built around those, which is why ₮ causes trouble.

## Where it goes wrong

- **Stakes.** A tracker reads the stake from the header. If it doesn't recognise ₮, it can't tell you're playing 1c/2c, so your reports by stake may be wrong or empty.
- **Money columns.** Winnings, rake and balance figures depend on the amounts being read as a currency.
- **Bankroll tracking.** Graphs and results in the wrong currency are hard to compare with other rooms.

The ₮ sign appears in cash games. In tournaments the structure is in chips, with no currency in the stack and blind lines.

## USDT and the dollar

USDT is designed to track the US dollar, so one ₮ is approximately one dollar. The usual way to make the file readable is to treat ₮ as `$` one for one and to add a currency code to the header. It's a close approximation, not an exact one, but it's the sensible choice for tracking results.

[PokerHandsConverter](/) does exactly this: it turns ₮ into `$` everywhere in the hand, adds the currency code to the header, and leaves tournament chip counts bare. The conversion also rewrites the other fields trackers are picky about; see [the time zone problem](/blog/coinpoker-hand-history-timezone) for the timestamp.

## Rake and fees are in ₮ too

The summary line reports the total pot and rake in ₮. A **Splash Fee** line on some tables, which trackers don't understand, is a common reason for rejected hands. [Fixing "Invalid Pot Size" errors](/blog/fix-invalid-pot-size-error-coinpoker-import) explains it, and [what rake is and how it affects your win rate](/blog/what-is-rake-in-online-poker) covers the basics.

## Next step

Once the amounts are in a format your tracker reads, import the file. [How to import CoinPoker hands into PokerTracker 4](/blog/import-coinpoker-hands-pokertracker-4) covers PokerTracker, and [Hold'em Manager 3](/blog/import-coinpoker-hands-holdem-manager-3) has its own guide.
