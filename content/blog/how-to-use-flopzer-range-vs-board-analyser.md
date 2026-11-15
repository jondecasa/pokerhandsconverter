---
title: "How to Use Flopzer: Analyse Your Range on Any Flop, Turn and River"
slug: how-to-use-flopzer-range-vs-board-analyser
excerpt: "Paint a range, deal a board and see how many combos are top pair, draws or sets, and how much equity each group has. A practical walkthrough of the Flopzer analyser."
meta_title: "Flopzer: Range vs Board Analyser, Step-by-Step Guide"
meta_description: "Learn how to load ranges, set a board, read the statistics table, filter hands by category and check equity with the Flopzer range analyser."
published: true
published_at: "2026-11-22T08:00:00+00:00"
---

Most postflop mistakes start with a wrong picture of the ranges. You think the opponent's range is full of draws on a wet board, but it is mostly top pair; you think your range is strong, but half of it has missed. **Flopzer** shows you that picture in numbers: you give it a range for each player and a board, and it tells you what each range *contains* and how it *performs*.

Everything is calculated in your browser, so it answers as you click. It is part of the subscriber area: open **Flopzer** in the left menu.

## The layout in one minute

The page has two range boxes, **Your range** and **Opponent range**, a **board** with its equity, a **statistics table** and a block of extra analysis. Click a box to work on that range; the grid, weights and statistics always refer to the selected one.

## Step 1: Build the ranges

Each range is a 13×13 grid: pairs on the diagonal, suited hands above it and offsuit hands below.

- **Paint on the grid.** Click or drag over cells to paint them with the selected weight; painting a cell that already has that weight removes it. The quick buttons **Pocket**, **Broadway**, **Suited** and **All hands** select whole families, **Clear** empties the grid and **Undo** reverts the last change.
- **Load a range.** In each box, **Load a range** opens a window with the charts of the Ranges section: open raises, defences against an open, defences against a 3-bet and more. Pick a chart and an action (for example the *OR* hands, or "everything but fold") and it is loaded straight onto the grid. This is the fastest way to compare your real preflop ranges against each other.
- **Copy to opponent.** Copies the selected range to the other box, handy for studying a mirror spot and then trimming one side.
- **Save your own.** Type a name under the grid and press **Save**; the range appears as a button you can load into either box later. Saved ranges stay in that browser.

### Weights

Not every hand in a range is played every time. Under the grid, choose a weight (**75%**, **50%** or **25%**, or **No weight** for 100%) and paint with it. The cell fills from the bottom up in proportion to the weight, so a half-weighted hand looks half full. A legend under the grid lists the weights in use and how many combos each carries.

## Step 2: Set the board

Pick the flop cards in the card selector, then the turn and the river when you reach them, or press **Random flop** and **Random card** to deal them for you. The flop alone is enough to start. Cards on the board are automatically removed from both ranges, because those combos can no longer exist.

**Dead cards** work the same way: mark cards you know are gone (for instance folded hole cards you saw) and the affected combos disappear from the counts.

## Step 3: Read the statistics table

This is the heart of the tool. Each row is a category, with a bar, the number of **combos**, its **share** of the range and its **equity** against the other range. The table has three sections:

- **Made hands.** Straight and better, set, two pair, overpair, top pair, middle pair, pocket pair below the second card, bottom pair, pocket pair below the board and no made hand.
- **Draws.** Flush draw, open-ended straight draw, gutshot, overcards with a backdoor flush draw and backdoor flush draws.
- **Combinations.** Hands that have a pair *and* a draw, or two draws at once, such as flush draw plus pair or flush draw plus open-ended.

A hand sits in exactly one made-hand row and at most one draw or combination row, so the numbers add up instead of counting a hand twice. The rows marked with an asterisk unfold into more detail.

### Use the rows to filter

Each row has a **tick** and a **cross**. Tick a row to select those hands, cross it to leave them out. The block headers have the same pair of buttons to mark a whole section at once. The hands that pass your filters light up in **amber on the grid**, so you see *where* they sit and not only how many there are, and the footer tells you how many combos pass.

Once the amber part is what you want, a bar appears with three buttons:

- **Keep only these** turns the selected range into just those hands. This is how you follow a range from street to street: on the flop keep top pair and better plus the flush draws, deal the turn and read the table again.
- **Remove these** does the opposite and deletes them from the range.
- **Clear filters** drops the selection without touching the range.

If you change your mind, **Undo** under the grid reverts the last change. Hovering a hand on the grid shows its detail.

## Step 4: Check the equity

The board block shows the **equity of each range** on the current board, calculated exactly over all the runouts that can still come. When you have only the flop, it includes every turn and river.

To test a single hand, use **Your hand**: pick two cards from the deck. They cannot be on the board, dead, or repeated, so there are no impossible hands. You get its category (for example top pair plus a flush draw) and its equity against the opponent range.

## Step 5: Go deeper with More analysis

Under the table there are four tabs:

- **Equity matrix.** The average equity of every cell of the grid. It shows which parts of your range carry the equity.
- **Hotness.** For each possible next card, how much it helps or hurts each range. Cards that raise your equity are marked, so you can see which turns you want to bet and which you want to slow down on.
- **Equity chart.** The equity of both ranges as a stepped curve, which shows how much of the range is strong and how much is weak.
- **Groups.** A pie that sorts the range into better than a pair, strong pair, other pair, draw and nothing.

## Three questions worth asking

1. **Who has more strong hands?** On a given flop, compare the share of top pair or better. The player with more of them can bet bigger and more often.
2. **How much of my range is nothing?** If a third of the range has missed, check-calling or giving up with those hands has to be part of the plan.
3. **Which cards change the board?** Use Hotness to find the turns where your range improves and the opponent's does not.

The best way to build intuition is to take a real hand from your sessions, load the preflop ranges for it and compare your reading of the spot with the numbers. If you want to drill the preflop part first, read [how to use Quiz Ranges](/blog/how-to-use-quiz-ranges-preflop-training), and to put numbers on your own tendencies see [fold to c-bet](/blog/fold-to-cbet-poker-stat-explained) and [aggression factor](/blog/aggression-factor-poker-stat-explained).

## Limits to keep in mind

Flopzer works with one opponent, so it does not calculate multiway pots. Ranges are a model: a real opponent can deviate from them, so use the output to form questions about a spot, not as a verdict. And your last ranges and board are remembered in that browser, so on a shared computer clear them before you leave.
