---
title: "WWSF in Poker: What \"Won When Saw Flop\" Tells You"
slug: wwsf-won-when-saw-flop-poker-stat-explained
excerpt: "WWSF shows how often a player wins the pot once they see the flop. Here is how it is counted, what a normal number is, and how it fits with WTSD and W$SD."
meta_title: "WWSF (Won When Saw Flop) in Poker: Meaning and Formula"
meta_description: "What WWSF (W$WSF) measures, how to calculate it, typical numbers, how it differs from W$SD, and how to read it together with WTSD to spot leaks and exploit opponents."
published: true
published_at: "2026-12-02T09:00:00+00:00"
---

## What WWSF measures

**WWSF** stands for *won when saw flop*. Some trackers call it **W$WSF** (won money when saw flop). It is the share of the hands where a player saw the flop in which they ended up winning the pot, whether at showdown or because everyone else folded.

```
WWSF = hands won after seeing the flop / hands where the flop was seen
```

It is a postflop result stat, and it tells you how well a player turns seeing a flop into winning a pot.

## A worked example

You see 200 flops. You win the pot in 92 of them, some at showdown and some because your opponents folded. Your WWSF is 92 / 200 = 46%.

## What a normal number looks like

Players who win are usually somewhere in the mid 40s, and a very good one may sit a little higher. A number well below that means the player is losing too many pots after the flop. Treat these as rough guides, since the value depends on stakes, game type and how loose the player is before the flop.

## How it differs from W$SD

[W$SD](/blog/wtsd-and-wsd-poker-stats-explained) counts only the hands that reached showdown, so it tells you whether a player wins when the cards are shown. WWSF counts every pot that was won after the flop, including those won with a bet that made everyone fold. That is the reason WWSF depends so much on how often a player bets and raises and how often the others give up.

## Reading it with the other stats

Looking at WWSF alone does not say much. Combined with others it helps a lot:

- **High WWSF, low WTSD.** The player wins by betting and making people fold, not by going to showdown. They are aggressive and effective, or they play only strong hands.
- **Low WWSF, high WTSD.** The player sees many flops and goes to showdown a lot, but loses. A classic calling station: they call too much and lose.
- **Low WWSF and low WTSD.** The player folds often after the flop and gives up on pots. Betting at them after the flop works well. See [fold to c-bet](/blog/fold-to-cbet-poker-stat-explained).
- **High WWSF with a very tight VPIP.** Tight players naturally win more of the pots they enter, so a high number alone is not a sign of skill.

## Using it on yourself

If your WWSF is low while you play a lot of hands, you are probably entering too many pots and losing them after the flop. A lower [VPIP](/blog/understanding-vpip-pfr-3bet-hud-stats) often raises WWSF. If it is low even with a tight range, look at how you play after the flop: whether you c-bet enough ([c-bet%](/blog/cbet-percentage-poker-stat-explained)) and how often you give up, and review the hands as in [reviewing your hands without a HUD](/blog/review-poker-hands-without-a-hud).

## One caution

WWSF includes small pots that were taken on the flop, so it does not weigh how much money was won. A player can have a high WWSF and still lose money by winning small pots and losing big ones. Always read it next to your [bb/100](/blog/bb-per-100-poker-win-rate-explained).

## Keep your tracker reading the right hands

Every stat on this page is only as good as the hand histories behind it. If you play on CoinPoker, [convert your hands with PokerHandsConverter](/) before importing them into PokerTracker 4 or Hold'em Manager 3, so the numbers your tracker shows are the ones you actually played.
